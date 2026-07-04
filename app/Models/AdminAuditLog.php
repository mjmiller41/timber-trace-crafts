<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditLog extends Model
{
    /**
     * Audit rows are append-only; only created_at is tracked.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'actor_email',
        'method',
        'route_name',
        'path',
        'subject_type',
        'subject_id',
        'status_code',
        'ip_address',
        'user_agent',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A short human label for the logged action, derived from the route name
     * (e.g. "orders.status") or falling back to "METHOD path".
     */
    public function label(): string
    {
        if ($this->route_name) {
            return (string) preg_replace('/^admin\./', '', $this->route_name);
        }

        return $this->method.' '.$this->path;
    }
}
