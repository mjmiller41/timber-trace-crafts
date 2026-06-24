<?php

namespace App\Services\Etsy;

class SyncResult
{
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $skipped = 0,
        public int $failed = 0,
    ) {}

    public function summary(): string
    {
        return "Created: {$this->created} | Updated: {$this->updated} | Skipped: {$this->skipped} | Failed: {$this->failed}";
    }
}
