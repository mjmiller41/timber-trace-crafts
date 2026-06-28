<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = User::where('role', 'customer')
            ->withCount('orders')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $user): View
    {
        $user->load([
            'orders' => fn ($q) => $q->orderByDesc('created_at')->limit(20),
            'addresses',
            'reviews',
        ]);

        return view('admin.customers.show', ['customer' => $user]);
    }
}
