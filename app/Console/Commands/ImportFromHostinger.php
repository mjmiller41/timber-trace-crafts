<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportFromHostinger extends Command
{
    protected $signature = 'db:import-hostinger
                            {--host=195.35.61.20}
                            {--port=3306}
                            {--database=u903552178_ttc}
                            {--username=u903552178_ttc_admin}
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
        'sessions',
        'jobs',
        'cache',
    ];

    public function handle(): int
    {
        $password = $this->option('password');

        if (! $password) {
            $password = $this->secret('Hostinger DB password');
        }

        config(['database.connections.hostinger' => [
            'driver' => 'mysql',
            'host' => $this->option('host'),
            'port' => $this->option('port'),
            'database' => $this->option('database'),
            'username' => $this->option('username'),
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

        if (empty($rows)) {
            $this->line("  <fg=gray>empty</> {$table}");

            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::table($table)->truncate();
        DB::statement('PRAGMA foreign_keys = ON');

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table($table)->insert($chunk);
        }

        $this->line("  <fg=green>✓</> {$table}: ".count($rows).' rows');
    }
}
