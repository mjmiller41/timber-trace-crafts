<?php

namespace App\Services;

class CartService
{
    public static function getCart(): array
    {
        return session('cart', []);
    }

    public static function saveCart(array $cart): void
    {
        session(['cart' => $cart]);
        session(['cart_count' => self::itemCount($cart)]);
    }

    public static function itemCount(array $cart): int
    {
        return array_sum(array_column($cart, 'qty'));
    }

    public static function subtotal(array $cart): float
    {
        return array_sum(array_map(
            fn ($i) => ($i['price'] + ($i['personalization_price'] ?? 0)) * $i['qty'],
            $cart
        ));
    }

    public static function clear(): void
    {
        session()->forget(['cart', 'cart_count', 'coupon']);
    }

    public static function add(array $item): void
    {
        $cart = self::getCart();
        $key = $item['row_key'];

        if (isset($cart[$key])) {
            $cart[$key]['qty'] += $item['qty'];
        } else {
            $cart[$key] = $item;
        }

        self::saveCart($cart);
    }

    public static function update(string $key, int $qty): void
    {
        $cart = self::getCart();

        if (isset($cart[$key])) {
            if ($qty <= 0) {
                unset($cart[$key]);
            } else {
                $cart[$key]['qty'] = $qty;
            }
        }

        self::saveCart($cart);
    }

    public static function remove(string $key): void
    {
        $cart = self::getCart();
        unset($cart[$key]);
        self::saveCart($cart);
    }

    public static function mergeSessions(int $userId): void
    {
        // After login: persist session cart to DB (Phase 2 enhancement)
        // For now, session cart persists as-is
    }
}
