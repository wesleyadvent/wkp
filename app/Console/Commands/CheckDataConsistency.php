<?php

namespace App\Console\Commands;

use App\Services\DataConsistencyAudit;
use Illuminate\Console\Command;

class CheckDataConsistency extends Command
{
    protected $signature = 'data:check-consistency {--json : Output machine-readable results}';

    protected $description = 'Read-only checks for transaction totals, stock, references, snapshots, and account assignments';

    public function handle(DataConsistencyAudit $audit): int
    {
        $results = $audit->run();
        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['Check', 'Issues', 'Sample IDs'], collect($results)->map(fn ($result, $name) => [$name, $result['count'], implode(', ', $result['sample_ids'])])->values()->all());
            $this->info('Read-only audit: no records were changed.');
        }

        return collect($results)->contains(fn ($result) => $result['count'] > 0) ? self::FAILURE : self::SUCCESS;
    }
}
