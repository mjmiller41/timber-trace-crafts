<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class EtsyAdminTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_etsy_admin_page_loads_when_disconnected(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.etsy.index'));

        $response->assertOk();
        $response->assertSee('Connect to Etsy');
    }

    public function test_etsy_admin_page_shows_connected_state(): void
    {
        Setting::set('etsy.access_token', Crypt::encryptString('some-token'));
        Setting::set('etsy.shop_id', '12345678');

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.etsy.index'));

        $response->assertOk();
        $response->assertSee('Connected');
    }

    public function test_connect_redirects_to_etsy_oauth_url(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.etsy.connect'));

        $response->assertRedirect();
        $this->assertStringContainsString('etsy.com/oauth/connect', $response->headers->get('Location'));
    }

    public function test_disconnect_clears_etsy_settings(): void
    {
        Setting::set('etsy.access_token', Crypt::encryptString('some-token'));
        Setting::set('etsy.shop_id', '12345');

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.etsy.disconnect'));

        $response->assertRedirect(route('admin.etsy.index'));
        $this->assertNull(Setting::get('etsy.access_token'));
        $this->assertNull(Setting::get('etsy.shop_id'));
    }
}
