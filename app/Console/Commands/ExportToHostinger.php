<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ExportToHostinger extends Command
{
    protected $signature = 'db:export-hostinger
                            {--host= : Defaults to HOSTINGER_DB_HOST}
                            {--port= : Defaults to HOSTINGER_DB_PORT (3306)}
                            {--database= : Defaults to HOSTINGER_DB_DATABASE}
                            {--username= : Defaults to HOSTINGER_DB_USERNAME}
                            {--password=}
                            {--tables=* : Specific tables to push (default: all content + account tables)}
                            {--dry-run : Connect, run preflight, and report counts without writing}
                            {--skip-backup : Do NOT dump current Hostinger data before overwriting (not recommended)}
                            {--force : Skip the final confirmation prompt}';

    protected $description = 'Overwrite the Hostinger MySQL database with data from the local SQLite database';

    /**
     * Target tables in foreign-key-safe order. Ephemeral/runtime tables
     * (migrations, sessions, cache, jobs, password resets) are intentionally
     * excluded — Hostinger manages its own schema/runtime state.
     *
     * @var list<string>
     */
    private const TABLE_ORDER = [
        'users',
        'addresses',
        'categories',
        'tags',
        'media',
        'products',
        'product_variation_types',
        'product_variants',
        'product_media',
        'product_tags',
        'shipping_methods',
        'tax_rates',
        'coupons',
        'orders',
        'order_items',
        'order_status_history',
        'shipments',
        'wishlists',
        'restock_requests',
        'journal_posts',
        'journal_post_tags',
        'pages',
        'settings',
        'contact_submissions',
        'product_reviews',
        'etsy_shop_sections',
    ];

    public function handle(): int
    {
        $password = $this->option('password') ?: env('HOSTINGER_DB_PASSWORD') ?: $this->secret('Hostinger DB password');

        config(['database.connections.hostinger' => [
            'driver' => 'mysql',
            'host' => $this->option('host') ?: env('HOSTINGER_DB_HOST'),
            'port' => $this->option('port') ?: env('HOSTINGER_DB_PORT', 3306),
            'database' => $this->option('database') ?: env('HOSTINGER_DB_DATABASE'),
            'username' => $this->option('username') ?: env('HOSTINGER_DB_USERNAME'),
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ]]);

        $remote = DB::connection('hostinger');

        $this->info('Connecting to Hostinger...');

        try {
            $remote->getPdo();
        } catch (\Throwable $e) {
            $this->error('Cannot connect to Hostinger: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Connected.');

        $remoteTables = collect($remote->select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->all();

        $localTables = array_map(
            fn ($t) => str_contains($t, '.') ? explode('.', $t, 2)[1] : $t,
            Schema::getTableListing()
        );

        $requested = $this->option('tables') ?: self::TABLE_ORDER;

        // Keep the declared FK-safe order; append any requested extras at the end.
        $tables = array_values(array_unique(array_merge(
            array_intersect(self::TABLE_ORDER, $requested),
            array_diff($requested, self::TABLE_ORDER),
        )));

        // Preflight: every target table must exist on both sides with all local columns present remotely.
        $plan = [];
        $blocked = false;

        foreach ($tables as $table) {
            if (! in_array($table, $localTables, true)) {
                $this->line("  <fg=gray>skip</> {$table} (not in local DB)");

                continue;
            }

            if (! in_array($table, $remoteTables, true)) {
                $this->error("  MISSING on Hostinger: {$table} — deploy migrations first.");
                $blocked = true;

                continue;
            }

            $localCols = Schema::getColumnListing($table);
            $remoteCols = collect($remote->select("SHOW COLUMNS FROM `{$table}`"))
                ->map(fn ($c) => ((array) $c)['Field'])
                ->all();

            $missing = array_diff($localCols, $remoteCols);

            if ($missing !== []) {
                $this->error("  COLUMN MISMATCH on {$table}: Hostinger missing [".implode(', ', $missing).'] — deploy migrations first.');
                $blocked = true;

                continue;
            }

            $plan[$table] = [
                'local' => DB::table($table)->count(),
                'remote' => $remote->table($table)->count(),
            ];
        }

        if ($blocked) {
            $this->newLine();
            $this->error('Preflight failed: Hostinger schema is out of date. Run `php artisan migrate --force` on the server, then retry.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Table', 'Local rows → push', 'Hostinger rows (overwritten)'],
            collect($plan)->map(fn ($c, $t) => [$t, $c['local'], $c['remote']])->values()->all()
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run only — no data written.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->warn('This will TRUNCATE the tables above on Hostinger and replace them with local data.');

            if (! $this->confirm('Proceed with the overwrite?', false)) {
                $this->info('Aborted. Nothing written.');

                return self::SUCCESS;
            }
        }

        // Backup current Hostinger data before overwriting.
        if (! $this->option('skip-backup')) {
            $this->backupRemote($remote, array_keys($plan));
        }

        // Overwrite.
        $remote->statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach (array_keys($plan) as $table) {
                $this->pushTable($remote, $table);
            }
        } finally {
            $remote->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->newLine();
        $this->info('Export complete.');

        return self::SUCCESS;
    }

    /**
     * Dump current remote rows to timestamped JSON files so the overwrite is reversible.
     *
     * @param  list<string>  $tables
     */
    private function backupRemote(Connection $remote, array $tables): void
    {
        $dir = storage_path('app/backups/hostinger-'.now()->format('Ymd-His'));
        File::ensureDirectoryExists($dir);

        foreach ($tables as $table) {
            $rows = $remote->table($table)->get()->map(fn ($r) => (array) $r)->all();
            File::put("{$dir}/{$table}.json", json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $this->line('  <fg=cyan>backup</> wrote '.count($tables)." table(s) to {$dir}");
    }

    private function pushTable(Connection $remote, string $table): void
    {
        $rows = DB::table($table)->get()->map(fn ($r) => (array) $r)->all();

        $remote->table($table)->truncate();

        if ($rows === []) {
            $this->line("  <fg=gray>empty</> {$table}");

            return;
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            $remote->table($table)->insert($chunk);
        }

        $this->line("  <fg=green>✓</> {$table}: ".count($rows).' rows');
    }
}
