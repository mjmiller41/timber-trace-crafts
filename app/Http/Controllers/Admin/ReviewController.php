<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(): View
    {
        $reviews = ProductReview::with(['product', 'user'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.reviews.index', compact('reviews'));
    }

    public function updateStatus(Request $request, ProductReview $review): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,approved,rejected'],
        ]);

        $review->update(['status' => $validated['status']]);

        return redirect()->route('admin.reviews.index')->with('success', 'Review status updated.');
    }
}
