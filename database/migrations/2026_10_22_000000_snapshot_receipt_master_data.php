<?php

use App\Services\ReceiptSnapshot;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (ReceiptSnapshot::MODELS as $class) {
            $table = (new $class)->getTable();
            // A large backfill can be safely resumed after interruption.
            if (! Schema::hasColumn($table, 'receipt_snapshot')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->json('receipt_snapshot')->nullable());
            }
            $relations = ReceiptSnapshot::relations(new $class);
            if (in_array('product', $relations, true)) {
                $relations[] = 'product.kategoriBarang';
            }
            $class::with($relations)->whereNull('receipt_snapshot')->chunkById(500, function ($rows) use ($table): void {
                $cases = [];
                $bindings = [];
                foreach ($rows as $row) {
                    // Preserve timestamps and totals while freezing available legacy data.
                    $cases[] = 'WHEN ? THEN ?';
                    $bindings[] = $row->id;
                    $bindings[] = json_encode(ReceiptSnapshot::capture($row), JSON_THROW_ON_ERROR);
                }
                $grammar = DB::connection()->getQueryGrammar();
                $ids = $rows->modelKeys();
                // One update per batch avoids hundreds of thousands of round trips.
                // UPDATE (rather than upsert) cannot resurrect a deleted record.
                DB::update(
                    'UPDATE '.$grammar->wrapTable($table)
                    .' SET '.$grammar->wrap('receipt_snapshot').' = CASE '.$grammar->wrap('id').' '.implode(' ', $cases).' END'
                    .' WHERE '.$grammar->wrap('id').' IN ('.implode(',', array_fill(0, count($ids), '?')).')'
                    .' AND '.$grammar->wrap('receipt_snapshot').' IS NULL',
                    array_merge($bindings, $ids),
                );
            });
        }
    }

    public function down(): void
    {
        foreach (ReceiptSnapshot::MODELS as $class) {
            Schema::table((new $class)->getTable(), fn (Blueprint $table) => $table->dropColumn('receipt_snapshot'));
        }
    }
};
