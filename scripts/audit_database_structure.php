<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Reads metadata and data integrity; writes reports and schema-only exports.
// No application schema or data is changed.
$db = DB::connection();
$quote = fn (string $name) => '`'.str_replace('`', '``', $name).'`';
$report = [
    'database' => $db->getDatabaseName(),
    'audited_at' => now()->toIso8601String(),
    'server' => $db->selectOne('SELECT VERSION() AS version, @@version_comment AS comment, @@sql_mode AS sql_mode, @@lower_case_table_names AS lower_case_table_names'),
    'tables' => $db->select("SELECT TABLE_NAME AS name, ENGINE AS engine, TABLE_COLLATION AS collation FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME"),
    'tables_without_primary_keys' => $db->select("SELECT t.TABLE_NAME AS name FROM information_schema.TABLES t WHERE t.TABLE_SCHEMA = DATABASE() AND t.TABLE_TYPE='BASE TABLE' AND NOT EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS c WHERE c.TABLE_SCHEMA=t.TABLE_SCHEMA AND c.TABLE_NAME=t.TABLE_NAME AND c.CONSTRAINT_TYPE='PRIMARY KEY')"),
];
$columns = [];
foreach ($db->select('SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name, COLUMN_TYPE AS column_type, IS_NULLABLE AS nullable, CHARACTER_SET_NAME AS charset, COLLATION_NAME AS collation FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,ORDINAL_POSITION') as $column) {
    $columns[$column->table_name][$column->column_name] = $column;
}
$indexes = [];
foreach ($db->select('SELECT TABLE_NAME AS table_name, INDEX_NAME AS index_name, NON_UNIQUE AS non_unique, COLUMN_NAME AS column_name, SUB_PART AS prefix_length FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX') as $index) {
    $indexes[$index->table_name][$index->index_name]['columns'][] = $index->column_name;
    $indexes[$index->table_name][$index->index_name]['non_unique'] = (int) $index->non_unique;
    $indexes[$index->table_name][$index->index_name]['prefixes'][] = $index->prefix_length;
}
$foreignKeys = [];
foreach ($db->select('SELECT k.TABLE_NAME AS child_table, k.CONSTRAINT_NAME AS constraint_name, k.COLUMN_NAME AS child_column, k.REFERENCED_TABLE_NAME AS parent_table, k.REFERENCED_COLUMN_NAME AS parent_column, r.UPDATE_RULE AS update_rule, r.DELETE_RULE AS delete_rule FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.TABLE_NAME=k.TABLE_NAME AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.TABLE_NAME,k.CONSTRAINT_NAME,k.ORDINAL_POSITION') as $key) {
    $id = $key->child_table.'.'.$key->constraint_name;
    $foreignKeys[$id] ??= ['child_table' => $key->child_table, 'name' => $key->constraint_name, 'parent_table' => $key->parent_table, 'delete_rule' => $key->delete_rule, 'update_rule' => $key->update_rule, 'child_columns' => [], 'parent_columns' => []];
    $foreignKeys[$id]['child_columns'][] = $key->child_column;
    $foreignKeys[$id]['parent_columns'][] = $key->parent_column;
}
$report['foreign_keys'] = array_values($foreignKeys);
$report['type_mismatches'] = $report['missing_fk_indexes'] = $report['unindexed_parent_keys'] = $report['invalid_set_null_rules'] = $report['orphan_records'] = [];
$hasIndex = function (string $table, array $wanted, bool $unique = false) use ($indexes): bool {
    foreach ($indexes[$table] ?? [] as $index) {
        if ((! $unique || $index['non_unique'] === 0) && array_slice($index['columns'], 0, count($wanted)) === $wanted && ! array_filter(array_slice($index['prefixes'], 0, count($wanted)), fn ($length) => $length !== null)) {
            return true;
        }
    }

    return false;
};
fwrite(STDERR, 'Checking '.count($foreignKeys).' foreign keys...'.PHP_EOL);
foreach ($foreignKeys as $id => $key) {
    foreach ($key['child_columns'] as $position => $childColumn) {
        $child = $columns[$key['child_table']][$childColumn];
        $parent = $columns[$key['parent_table']][$key['parent_columns'][$position]];
        if ($child->column_type !== $parent->column_type || $child->charset !== $parent->charset || $child->collation !== $parent->collation) {
            $report['type_mismatches'][] = $id;
        }
        if ($key['delete_rule'] === 'SET NULL' && $child->nullable !== 'YES') {
            $report['invalid_set_null_rules'][] = $id;
        }
    }
    if (! $hasIndex($key['child_table'], $key['child_columns'])) {
        $report['missing_fk_indexes'][] = $id;
    }
    if (! $hasIndex($key['parent_table'], $key['parent_columns'], true)) {
        $report['unindexed_parent_keys'][] = $id;
    }
    $query = $db->table($key['child_table'].' as c');
    foreach ($key['child_columns'] as $column) {
        $query->whereNotNull('c.'.$column);
    }
    $count = $query->whereNotExists(function ($query) use ($key) {
        $query->selectRaw('1')->from($key['parent_table'].' as p');
        foreach ($key['child_columns'] as $position => $column) {
            $query->whereColumn('p.'.$key['parent_columns'][$position], 'c.'.$column);
        }
    })->count();
    if ($count > 0) {
        $report['orphan_records'][] = ['foreign_key' => $id, 'count' => $count];
    }
}
$report['unconstrained_id_columns'] = [];
foreach ($columns as $table => $fields) {
    foreach ($fields as $name => $column) {
        if (! preg_match('/(?:_id|_id_ref)$/', $name)) {
            continue;
        }
        $constrained = false;
        foreach ($foreignKeys as $key) {
            $constrained = $constrained || ($key['child_table'] === $table && in_array($name, $key['child_columns'], true));
        }
        if (! $constrained) {
            $report['unconstrained_id_columns'][] = $table.'.'.$name;
        }
    }
}
$report['json_checks'] = $db->select('SELECT TABLE_NAME AS table_name, CONSTRAINT_NAME AS name, CHECK_CLAUSE AS clause FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() ORDER BY TABLE_NAME,CONSTRAINT_NAME');
$report['unknown_configuration_categories'] = $db->table('konfigurasi_persediaan as c')->whereNotExists(fn ($query) => $query->selectRaw('1')->from('kategori_barang as k')->whereColumn('k.nama_kategori', 'c.kategori'))->count();
$report['legacy_daily_sequence_rows'] = $db->table('urutan_harian_barang')->count();
fwrite(STDERR, 'Running CHECK TABLE QUICK and exporting schema definitions...'.PHP_EOL);
$names = array_map(fn ($table) => $quote($table->name), $report['tables']);
$report['table_checks'] = $db->select('CHECK TABLE '.implode(', ', $names).' QUICK');
$report['table_check_issues'] = array_values(array_filter($report['table_checks'], fn ($row) => $row->Msg_type !== 'status' || $row->Msg_text !== 'OK'));

$directory = base_path('docs/database');
if (! is_dir($directory)) {
    mkdir($directory, 0777, true);
}
$schema = $quote($db->getDatabaseName());
$header = '-- Structure only: '.$db->getDatabaseName().' / '.$report['server']->version.PHP_EOL
    .'-- Generated '.$report['audited_at'].PHP_EOL;
$native = $header."SET FOREIGN_KEY_CHECKS=0;\nCREATE SCHEMA IF NOT EXISTS ".$schema." DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE ".$schema.";\n\n";
$workbench = $header."-- Import in Workbench: File > Import > Reverse Engineer MySQL Create Script.\n"
    ."-- MariaDB JSON aliases use native MySQL JSON here; foreign keys follow all CREATE TABLE statements.\n"
    .'CREATE SCHEMA IF NOT EXISTS '.$schema." DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE ".$schema.";\n\n";
$relations = [];
$jsonConversions = 0;
foreach ($report['tables'] as $table) {
    $definition = (array) $db->selectOne('SHOW CREATE TABLE '.$quote($table->name));
    $ddl = $definition['Create Table'];
    $native .= $ddl.";\n\n";
    $ddl = preg_replace('/ AUTO_INCREMENT=\d+\b/', '', $ddl);
    $ddl = preg_replace_callback('/(^\s*`[^`]+`\s+)longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin(.*?) CHECK \(json_valid\(`[^`]+`\)\)/m', function ($match) use (&$jsonConversions) {
        $jsonConversions++;

        return $match[1].'json'.$match[2];
    }, $ddl);
    $lines = [];
    foreach (explode("\n", $ddl) as $line) {
        if (str_starts_with(ltrim($line), 'CONSTRAINT ') && str_contains($line, ' FOREIGN KEY ')) {
            $relations[] = 'ALTER TABLE '.$quote($table->name).' ADD '.rtrim(trim($line), ',').';';
        } else {
            $lines[] = $line;
        }
    }
    $workbench .= preg_replace('/,\n\)/', "\n)", implode("\n", $lines)).";\n\n";
}
$native .= "SET FOREIGN_KEY_CHECKS=1;\n";
$workbench .= implode("\n", $relations)."\n";
$nativePath = $directory.'/wkp_demo_schema_mariadb.sql';
$workbenchPath = $directory.'/wkp_demo_schema_workbench.sql';
file_put_contents($nativePath, $native);
file_put_contents($workbenchPath, $workbench);
$report['exports'] = ['mariadb' => $nativePath, 'workbench' => $workbenchPath, 'create_table_statements' => count($report['tables']), 'foreign_key_statements' => count($relations), 'json_aliases_converted_for_workbench' => $jsonConversions];
$report['passed_integrity_checks'] = true;
foreach (['tables_without_primary_keys', 'type_mismatches', 'missing_fk_indexes', 'unindexed_parent_keys', 'invalid_set_null_rules', 'orphan_records', 'table_check_issues'] as $field) {
    $report['passed_integrity_checks'] = $report['passed_integrity_checks'] && $report[$field] === [];
}
file_put_contents(storage_path('app/private/database-structure-audit.json'), json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
echo json_encode(array_intersect_key($report, array_flip(['database', 'server', 'tables_without_primary_keys', 'type_mismatches', 'missing_fk_indexes', 'unindexed_parent_keys', 'invalid_set_null_rules', 'orphan_records', 'unconstrained_id_columns', 'unknown_configuration_categories', 'legacy_daily_sequence_rows', 'table_check_issues', 'exports', 'passed_integrity_checks'])), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
exit($report['passed_integrity_checks'] ? 0 : 1);
