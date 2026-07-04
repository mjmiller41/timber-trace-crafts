<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin session idle timeout
    |--------------------------------------------------------------------------
    |
    | Number of minutes of inactivity after which an authenticated admin is
    | logged out of the admin area. Only the /admin surface is affected —
    | customer sessions keep the framework's default session lifetime.
    |
    */

    'idle_timeout' => (int) env('ADMIN_IDLE_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Admin audit log
    |--------------------------------------------------------------------------
    |
    | When enabled, every state-changing admin request (POST/PUT/PATCH/DELETE)
    | is recorded to the admin_audit_logs table. Read requests are never
    | logged. Rows older than `retention_days` can be pruned by the
    | admin:prune-audit-log command.
    |
    */

    'audit' => [
        'enabled' => (bool) env('ADMIN_AUDIT_ENABLED', true),
        'retention_days' => (int) env('ADMIN_AUDIT_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shipping label "from" address
    |--------------------------------------------------------------------------
    |
    | Return address printed on Avery 5126 shipping labels. The store name is
    | taken from the store.name setting; only the postal address lines live
    | here. Leave blank to omit the return block from the label.
    |
    */

    'ship_from' => [
        'line1' => env('SHIP_FROM_LINE1', ''),
        'line2' => env('SHIP_FROM_LINE2', ''),
        'city' => env('SHIP_FROM_CITY', ''),
        'state' => env('SHIP_FROM_STATE', ''),
        'zip' => env('SHIP_FROM_ZIP', ''),
        'country' => env('SHIP_FROM_COUNTRY', 'US'),
    ],

];
