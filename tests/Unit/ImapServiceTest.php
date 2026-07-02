<?php

namespace Tests\Unit;

use App\Services\ImapService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class ImapServiceTest extends TestCase
{
    /**
     * The ext-imap extension isn't installed in this environment and the
     * constructor opens a real connection, so we exercise the hardening
     * helpers directly via reflection rather than constructing the service.
     */
    private function service(): ImapService
    {
        $reflection = new ReflectionClass(ImapService::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        // __destruct() reads $connection — initialize it so teardown doesn't
        // hit the "must not be accessed before initialization" typed-property error.
        $connection = $reflection->getProperty('connection');
        $connection->setAccessible(true);
        $connection->setValue($instance, null);

        return $instance;
    }

    private function callPrivate(string $method, array $args): mixed
    {
        $reflection = new ReflectionMethod(ImapService::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($this->service(), $args);
    }

    #[Test]
    public function it_falls_back_to_now_for_an_unparseable_date_header(): void
    {
        $result = $this->callPrivate('safeParseDate', ['this is not a date at all']);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertTrue($result->diffInSeconds(now()) < 5);
    }

    #[Test]
    public function it_falls_back_to_now_for_a_null_date_header(): void
    {
        $result = $this->callPrivate('safeParseDate', [null]);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertTrue($result->diffInSeconds(now()) < 5);
    }

    #[Test]
    public function it_parses_a_well_formed_date_header(): void
    {
        $result = $this->callPrivate('safeParseDate', ['Thu, 1 Jan 2026 12:00:00 +0000']);

        $this->assertEquals('2026-01-01 12:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_falls_back_to_the_raw_body_for_an_unknown_charset(): void
    {
        $result = $this->callPrivate('safeConvertToUtf8', ['hello world', 'not-a-real-charset']);

        $this->assertEquals('hello world', $result);
    }

    #[Test]
    public function it_converts_a_known_charset_to_utf8(): void
    {
        $latin1 = mb_convert_encoding('café', 'ISO-8859-1', 'UTF-8');

        $result = $this->callPrivate('safeConvertToUtf8', [$latin1, 'ISO-8859-1']);

        $this->assertEquals('café', $result);
    }
}
