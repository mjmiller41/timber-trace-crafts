<?php

namespace App\Console\Commands;

use App\Models\AdminAuditLog;
use Illuminate\Console\Command;

class PruneAdminAuditLog extends Command
{
    protected $signature = 'admin:prune-audit-log
                            {--days= : Override the configured retention window}';

    protected $description = 'Delete admin audit-log rows older than the configured retention window';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('admin.audit.retention_days', 365));

        if ($days <= 0) {
            $this->info('Retention disabled (days <= 0); nothing pruned.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $deleted = AdminAuditLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} audit-log row(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
