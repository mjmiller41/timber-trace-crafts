<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ImportFromHostinger extends Command
{
    protected $signature = 'db:import-hostinger
                            {--host= : Defaults to HOSTINGER_DB_HOST}
                            {--port= : Defaults to HOSTINGER_DB_PORT (3306)}
                            {--database= : Defaults to HOSTINGER_DB_DATABASE}
                            {--username= : Defaults to HOSTINGER_DB_USERNAME}
                            {--password=}
                            {--tables=* : Specific tables to import (default: all)}';

    protected $description = 'Copy data from Hostinger MySQL into the local SQLite database';

    private const TABLE_ORDER = [
        'users',
        'categories',
        'tags',
        'media',
        'products',
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
        // Ephemeral/runtime tables (sessions, jobs, cache) are intentionally
        // excluded — importing Hostinger's would clobber the local live queue.
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

        $this->info('Connecting to Hostinger...');

        try {
            DB::connection('hostinger')->getPdo();
        } catch (\Exception $e) {
            $this->error('Cannot connect to Hostinger: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Connected. Starting import...');

        $tables = $this->option('tables') ?: self::TABLE_ORDER;

        $hostingerTables = collect(DB::connection('hostinger')->select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->toArray();

        $localTables = array_map(
            fn ($t) => str_contains($t, '.') ? explode('.', $t, 2)[1] : $t,
            Schema::getTableListing()
        );

        foreach ($tables as $table) {
            if (! in_array($table, $hostingerTables)) {
                $this->line("  <fg=gray>skip</> {$table} (not in Hostinger)");

                continue;
            }

            if (! in_array($table, $localTables)) {
                $this->line("  <fg=gray>skip</> {$table} (not in local DB)");

                continue;
            }

            $this->importTable($table);
        }

        $this->newLine();
        $this->info('Import complete.');

        return self::SUCCESS;
    }

    private function importTable(string $table): void
    {
        $rows = DB::connection('hostinger')->table($table)->get()->map(fn ($r) => (array) $r)->toArray();

        $this->backupLocal($table);

        if (empty($rows)) {
            $this->line("  <fg=gray>empty</> {$table}");

            return;
        }

        // PRAGMA foreign_keys is a connection-level setting and a no-op mid-transaction
        // on SQLite, so it must be toggled outside the transaction that does the writes.
        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::transaction(function () use ($table, $rows) {
                DB::table($table)->truncate();

                foreach (array_chunk($rows, 100) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        $this->line("  <fg=green>✓</> {$table}: ".count($rows).' rows');
    }

    /** Dump the current local rows to a timestamped JSON file before truncating. */
    private function backupLocal(string $table): void
    {
        $dir = storage_path('app/backups/hostinger-import-'.now()->format('Ymd-His'));
        File::ensureDirectoryExists($dir);

        $rows = DB::table($table)->get()->map(fn ($r) => (array) $r)->all();
        File::put("{$dir}/{$table}.json", json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
