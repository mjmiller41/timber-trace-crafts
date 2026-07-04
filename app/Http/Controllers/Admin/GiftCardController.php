<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Services\GiftCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GiftCardController extends Controller
{
    public function __construct(private GiftCardService $giftCards) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $status = $request->input('status', '');

        $giftCards = GiftCard::query()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('code', 'like', $like)
                        ->orWhere('recipient_email', 'like', $like)
                        ->orWhere('recipient_name', 'like', $like)
                        ->orWhere('purchaser_email', 'like', $like);
                });
            })
            ->when($status === 'active', fn ($q) => $q->where('active', true)->where('balance', '>', 0))
            ->when($status === 'inactive', fn ($q) => $q->where('active', false))
            ->when($status === 'depleted', fn ($q) => $q->where('balance', '<=', 0))
            ->when($status === 'expired', fn ($q) => $q->whereNotNull('expires_at')->where('expires_at', '<', now()))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.gift-cards.index', compact('giftCards', 'search', 'status'));
    }

    public function create(): View
    {
        return view('admin.gift-cards.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'purchaser_email' => ['nullable', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $card = $this->giftCards->issue((float) $validated['amount'], [
            'recipient_email' => $validated['recipient_email'] ?? null,
            'recipient_name' => $validated['recipient_name'] ?? null,
            'purchaser_email' => $validated['purchaser_email'] ?? null,
            'message' => $validated['message'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'issue_note' => 'Issued manually via admin.',
        ]);

        return redirect()
            ->route('admin.gift-cards.show', $card)
            ->with('success', "Gift card {$card->code} issued for \${$card->initial_balance}.");
    }

    public function show(GiftCard $giftCard): View
    {
        $giftCard->load(['transactions' => fn ($q) => $q->orderByDesc('created_at'), 'transactions.order']);

        return view('admin.gift-cards.show', compact('giftCard'));
    }

    /**
     * Void (deactivate) a card. A card that has been partially redeemed still
     * holds customer value, so voiding one requires an explicit confirmation
     * flag to guard against accidental deactivation.
     */
    public function void(Request $request, GiftCard $giftCard): RedirectResponse
    {
        if ($this->isPartiallyRedeemed($giftCard) && ! $request->boolean('confirm')) {
            return redirect()
                ->route('admin.gift-cards.show', $giftCard)
                ->with('error', 'This card still holds a redeemable balance. Confirm the void to proceed.');
        }

        $giftCard->update(['active' => false]);

        return redirect()
            ->route('admin.gift-cards.show', $giftCard)
            ->with('success', 'Gift card voided.');
    }

    public function reactivate(GiftCard $giftCard): RedirectResponse
    {
        $giftCard->update(['active' => true]);

        return redirect()
            ->route('admin.gift-cards.show', $giftCard)
            ->with('success', 'Gift card reactivated.');
    }

    /**
     * Credit an amount back onto the card (e.g. goodwill or an off-order refund).
     */
    public function refund(Request $request, GiftCard $giftCard): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->giftCards->refund(
            $giftCard,
            (float) $validated['amount'],
            null,
            $validated['note'] ?? 'Credited via admin.',
        );

        return redirect()
            ->route('admin.gift-cards.show', $giftCard)
            ->with('success', 'Balance credited to gift card.');
    }

    private function isPartiallyRedeemed(GiftCard $giftCard): bool
    {
        return (float) $giftCard->balance > 0
            && (float) $giftCard->balance < (float) $giftCard->initial_balance;
    }
}
