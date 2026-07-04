<?php

namespace App\Services;

use App\Models\Cart;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CartService
{
    public function getCart(): array
    {
        return session('cart', []);
    }

    public function saveCart(array $cart): void
    {
        session(['cart' => $cart]);
        session(['cart_count' => $this->itemCount($cart)]);

        $this->persist($cart);
    }

    public function itemCount(array $cart): int
    {
        return array_sum(array_column($cart, 'qty'));
    }

    public function subtotal(array $cart): float
    {
        return array_sum(array_map(
            fn ($i) => ($i['price'] + ($i['personalization_price'] ?? 0)) * $i['qty'],
            $cart
        ));
    }

    public function clear(): void
    {
        // Mark the persisted cart converted so the abandonment sweep never
        // emails a customer who has just checked out, then drop the session
        // pointer so the next visit starts a fresh cart row.
        $this->markConverted();

        session()->forget(['cart', 'cart_count', 'coupon', 'cart_token']);
    }

    public function add(array $item): void
    {
        $cart = $this->getCart();
        $key = $item['row_key'];

        if (isset($cart[$key])) {
            $cart[$key]['qty'] += $item['qty'];
        } else {
            $cart[$key] = $item;
        }

        $this->saveCart($cart);
    }

    public function update(string $key, int $qty): void
    {
        $cart = $this->getCart();

        if (isset($cart[$key])) {
            if ($qty <= 0) {
                unset($cart[$key]);
            } else {
                $cart[$key]['qty'] = $qty;
            }
        }

        $this->saveCart($cart);
    }

    public function remove(string $key): void
    {
        $cart = $this->getCart();
        unset($cart[$key]);
        $this->saveCart($cart);
    }

    /**
     * Attach an email (and the current user, if any) to the persisted cart so
     * an abandoned-cart reminder can reach the shopper. Safe to call from the
     * checkout flow the moment an address is known; no-op if the cart has not
     * been persisted yet.
     */
    public function attachIdentity(?string $email = null): void
    {
        $token = session('cart_token');
        if (! $token) {
            return;
        }

        try {
            $cart = Cart::where('token', $token)->first();
            if (! $cart) {
                return;
            }

            $updates = array_filter([
                'user_id' => auth()->id(),
                'email' => $email ?: auth()->user()?->email,
            ], fn ($v) => ! is_null($v));

            if ($updates) {
                $cart->forceFill($updates)->save();
            }
        } catch (\Throwable $e) {
            Log::warning('Cart identity attach failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Shadow-write the session cart to the `carts` table. Session remains the
     * source of truth for reads; the DB copy exists solely so idle carts can
     * be found and reminded. Never allowed to break the request — a DB hiccup
     * degrades to session-only behaviour.
     *
     * @param  array<string, array<string, mixed>>  $cart
     */
    private function persist(array $cart): void
    {
        try {
            $token = session('cart_token');

            if (empty($cart)) {
                // Emptied cart: reflect it so a stale row isn't reminded.
                if ($token) {
                    Cart::where('token', $token)->update([
                        'contents' => '[]',
                        'item_count' => 0,
                        'subtotal' => 0,
                        'last_activity_at' => now(),
                    ]);
                }

                return;
            }

            if (! $token) {
                $token = (string) Str::uuid();
                session(['cart_token' => $token]);
            }

            Cart::updateOrCreate(
                ['token' => $token],
                [
                    'user_id' => auth()->id(),
                    'email' => auth()->user()?->email,
                    'contents' => array_values($cart),
                    'item_count' => $this->itemCount($cart),
                    'subtotal' => round($this->subtotal($cart), 2),
                    'unsubscribe_token' => Cart::where('token', $token)->value('unsubscribe_token') ?? (string) Str::uuid(),
                    'last_activity_at' => now(),
                    'converted_at' => null,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Cart persistence failed', ['error' => $e->getMessage()]);
        }
    }

    private function markConverted(): void
    {
        $token = session('cart_token');
        if (! $token) {
            return;
        }

        try {
            Cart::where('token', $token)->whereNull('converted_at')->update([
                'converted_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Cart conversion mark failed', ['error' => $e->getMessage()]);
        }
    }
}
