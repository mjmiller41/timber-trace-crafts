<?php

namespace App\Services;

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
        session()->forget(['cart', 'cart_count', 'coupon']);
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
}
