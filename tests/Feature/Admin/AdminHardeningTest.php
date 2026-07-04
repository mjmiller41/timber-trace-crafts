<?php

namespace Tests\Feature\Admin;

use App\Models\AdminAuditLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // ---- Idle timeout -----------------------------------------------------

    #[Test]
    public function admin_stays_signed_in_within_the_idle_window(): void
    {
        config(['admin.idle_timeout' => 30]);

        $response = $this->actingAs($this->admin())
            ->withSession(['admin_last_activity' => now()->subMinutes(5)->getTimestamp()])
            ->get(route('admin.dashboard'));

        $response->assertOk();
    }

    #[Test]
    public function admin_is_logged_out_after_idle_timeout(): void
    {
        config(['admin.idle_timeout' => 30]);

        $response = $this->actingAs($this->admin())
            ->withSession(['admin_last_activity' => now()->subMinutes(31)->getTimestamp()])
            ->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    #[Test]
    public function idle_timeout_can_be_disabled(): void
    {
        config(['admin.idle_timeout' => 0]);

        $response = $this->actingAs($this->admin())
            ->withSession(['admin_last_activity' => now()->subDays(2)->getTimestamp()])
            ->get(route('admin.dashboard'));

        $response->assertOk();
    }

    // ---- Audit log --------------------------------------------------------

    #[Test]
    public function state_changing_admin_requests_are_audited(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create(['status' => 'processing', 'user_id' => User::factory()->create()->id]);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'shipped']);

        $this->assertDatabaseHas('admin_audit_logs', [
            'user_id' => $admin->id,
            'method' => 'PATCH',
            'route_name' => 'admin.orders.status',
            'subject_type' => 'Order',
            'subject_id' => (string) $order->id,
        ]);
    }

    #[Test]
    public function read_only_admin_requests_are_not_audited(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $this->assertDatabaseCount('admin_audit_logs', 0);
    }

    #[Test]
    public function audit_log_redacts_sensitive_fields(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create(['status' => 'processing', 'user_id' => User::factory()->create()->id]);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'status' => 'shipped',
            'password' => 'super-secret',
        ]);

        $log = AdminAuditLog::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->properties ?? []);
    }

    #[Test]
    public function audit_log_viewer_lists_events_for_admins(): void
    {
        AdminAuditLog::create([
            'method' => 'POST', 'path' => '/admin/coupons', 'route_name' => 'admin.coupons.store',
            'actor_email' => 'boss@example.com', 'created_at' => now(),
        ]);

        $this->actingAs($this->admin())->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSee('coupons.store');
    }

    #[Test]
    public function audit_log_viewer_is_forbidden_for_customers(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('admin.audit.index'))
            ->assertForbidden();
    }

    #[Test]
    public function prune_command_removes_stale_rows_only(): void
    {
        AdminAuditLog::create(['method' => 'POST', 'path' => '/x'])
            ->forceFill(['created_at' => now()->subDays(400)])->save();
        AdminAuditLog::create(['method' => 'POST', 'path' => '/y'])
            ->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->artisan('admin:prune-audit-log', ['--days' => 365])->assertSuccessful();

        $this->assertDatabaseCount('admin_audit_logs', 1);
    }

    // ---- Error-log viewer -------------------------------------------------

    #[Test]
    public function error_log_viewer_renders_and_filters_by_level(): void
    {
        // Use an isolated log filename so the real laravel.log is never touched.
        $name = 'ttc-test-'.getmypid().'.log';
        $path = storage_path('logs/'.$name);
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path,
            "[2026-07-04 10:00:00] production.ERROR: Something exploded {\"trace\":1}\n".
            "[2026-07-04 10:01:00] production.INFO: Just an fyi\n"
        );

        try {
            $this->actingAs($this->admin())->get(route('admin.errors.index', ['file' => $name, 'level' => 'ERROR']))
                ->assertOk()
                ->assertSee('Something exploded')
                ->assertDontSee('Just an fyi');
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function error_log_viewer_ignores_unknown_file_names(): void
    {
        // Path-traversal / arbitrary file names must not be read.
        $this->actingAs($this->admin())->get(route('admin.errors.index', ['file' => '../../.env']))
            ->assertOk();
    }

    // ---- Shipping label ---------------------------------------------------

    #[Test]
    public function admin_can_view_shipping_label(): void
    {
        $order = Order::factory()->create([
            'shipping_first_name' => 'Dana',
            'shipping_last_name' => 'Reed',
            'user_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($this->admin())->get(route('admin.orders.shipping-label', $order))
            ->assertOk()
            ->assertSee('Dana Reed')
            ->assertSee('Ship To');
    }

    #[Test]
    public function shipping_label_is_forbidden_for_customers(): void
    {
        $order = Order::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('admin.orders.shipping-label', $order))
            ->assertForbidden();
    }
}
