<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EditorResponsivenessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    #[Test]
    public function zencomposer_uses_a_fluid_shell_rather_than_a_fixed_height(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.journal.create'));

        $response->assertOk();
        // Fluid shell class + editor told to fill it; no hardcoded editor height.
        $response->assertSee('zencomposer-shell');
        $response->assertSee("height: '100%'", false);
        $response->assertSee('clamp(22rem, 60vh, 48rem)', false);
        $response->assertDontSee("height: '480px'", false);
    }

    #[Test]
    public function the_image_editor_overlay_has_a_small_screen_breakpoint(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.media.index'));

        $response->assertOk();
        $response->assertSee('@media (max-width: 640px)', false);
    }
}
