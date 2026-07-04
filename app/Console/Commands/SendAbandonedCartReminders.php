<?php

namespace App\Console\Commands;

use App\Mail\AbandonedCartMail;
use App\Models\Cart;
use App\Models\CartEmailSuppression;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'cart:send-abandoned-reminders
                            {--dry-run : List the carts that would be emailed without sending}';

    protected $description = 'Email a reminder for carts that have been idle with items and a known email address';

    public function handle(): int
    {
        $config = config('cart.reminders');
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! ($config['enabled'] ?? false)) {
            $this->info('Cart reminders are disabled (cart.reminders.enabled=false); nothing sent.');

            return self::SUCCESS;
        }

        $stages = collect($config['stages'] ?? [])->sortBy('stage')->values();
        if ($stages->isEmpty()) {
            $this->warn('No reminder stages configured; nothing to do.');

            return self::SUCCESS;
        }

        $maxAge = now()->subHours((int) ($config['max_age_hours'] ?? 168));
        $emailGuests = (bool) ($config['email_guests'] ?? false);

        $sent = 0;
        $skipped = 0;

        foreach ($stages as $stageConfig) {
            $stage = (int) $stageConfig['stage'];
            $idleBefore = now()->subHours((int) $stageConfig['after_hours']);

            $carts = Cart::query()
                ->whereNull('converted_at')
                ->where('item_count', '>', 0)
                ->whereNotNull('email')
                ->where('reminder_stage', $stage - 1)
                ->where('last_activity_at', '<=', $idleBefore)
                ->where('last_activity_at', '>=', $maxAge)
                ->when(! $emailGuests, fn ($q) => $q->whereNotNull('user_id'))
                ->get();

            foreach ($carts as $cart) {
                // Suppressed (unsubscribed) address — advance the stage so we
                // don't re-check it every run, but never send.
                if (CartEmailSuppression::suppresses($cart->email)) {
                    $cart->update(['reminder_stage' => $stage]);
                    $skipped++;

                    continue;
                }

                // The shopper may have completed the purchase through another
                // cart/session; if any order exists for this email, treat the
                // cart as converted and stop reminding.
                if (Order::where('guest_email', $cart->email)
                    ->orWhereHas('user', fn ($q) => $q->where('email', $cart->email))
                    ->exists()
                ) {
                    $cart->update(['converted_at' => now()]);
                    $skipped++;

                    continue;
                }

                if ($dryRun) {
                    $this->line("  [dry-run] stage {$stage} → {$cart->email} (cart #{$cart->id}, {$cart->item_count} item(s))");
                    $sent++;

                    continue;
                }

                try {
                    Mail::to($cart->email)->queue(new AbandonedCartMail($cart, $stage));

                    $cart->update([
                        'reminder_stage' => $stage,
                        'reminder_sent_at' => now(),
                    ]);
                    $sent++;
                } catch (\Throwable $e) {
                    Log::error('Abandoned-cart reminder failed', [
                        'cart' => $cart->id,
                        'stage' => $stage,
                        'error' => $e->getMessage(),
                    ]);
                    $skipped++;
                }
            }
        }

        $verb = $dryRun ? 'would send' : 'sent';
        $this->info("Abandoned-cart sweep complete: {$verb} {$sent} reminder(s), skipped {$skipped}.");

        return self::SUCCESS;
    }
}
