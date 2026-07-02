<?php

namespace Tests\Unit;

use App\Console\Commands\ImportFromHostinger;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class ImportFromHostingerTest extends TestCase
{
    #[Test]
    public function ephemeral_runtime_tables_are_never_imported(): void
    {
        // Importing Hostinger's jobs/sessions/cache would clobber the local
        // live queue and session state.
        $tableOrder = (new ReflectionClass(ImportFromHostinger::class))
            ->getConstant('TABLE_ORDER');

        $this->assertNotContains('jobs', $tableOrder);
        $this->assertNotContains('sessions', $tableOrder);
        $this->assertNotContains('cache', $tableOrder);
    }
}
