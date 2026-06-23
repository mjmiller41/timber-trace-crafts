<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\RestockRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RestockController extends Controller
{
    public function index(): View
    {
        $requests = RestockRequest::with('variant.product')
            ->whereNull('notified_at')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.restock.index', compact('requests'));
    }

    public function notify(ProductVariant $variant): RedirectResponse
    {
        $pending = RestockRequest::where('product_variant_id', $variant->id)
            ->whereNull('notified_at')
            ->get();

        foreach ($pending as $restockRequest) {
            Mail::raw(
                "Good news! The item you requested ({$variant->product->name} — {$variant->label}) is back in stock.",
                function ($mail) use ($restockRequest, $variant) {
                    $mail->to($restockRequest->email)
                        ->subject("{$variant->product->name} is back in stock!");
                }
            );

            $restockRequest->update(['notified' => true, 'notified_at' => now()]);
        }

        return redirect()->route('admin.restock.index')
            ->with('success', "Notified {$pending->count()} subscriber(s).");
    }
}
