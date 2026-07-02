<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Models\TaxRate;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_taxes_a_state_configured_in_lowercase(): void
    {
        // The admin-configured state list is stored as typed, not normalized —
        // the shipping state comparison must not silently disable tax because
        // of a case mismatch on either side.
        Setting::set('tax.apply_to_states', 'fl');
        TaxRate::create(['state_code' => 'FL', 'rate_percent' => 0.06, 'label' => 'Florida', 'active' => true]);

        $tax = app(TaxService::class)->calculate(100.00, 'fl');

        $this->assertEquals(6.00, $tax);
    }

    #[Test]
    public function it_does_not_tax_a_state_outside_the_configured_list(): void
    {
        Setting::set('tax.apply_to_states', 'FL');
        TaxRate::create(['state_code' => 'OR', 'rate_percent' => 0.08, 'label' => 'Oregon', 'active' => true]);

        $tax = app(TaxService::class)->calculate(100.00, 'OR');

        $this->assertEquals(0.0, $tax);
    }
}
