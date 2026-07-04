@extends('layouts.admin')

@section('page-title', 'Issue Gift Card')

@section('content')

<div style="max-width: 640px;">

    <div style="margin-bottom: 1rem;">
        <a href="{{ route('admin.gift-cards.index') }}" style="font-size: 0.8125rem; color: #6b7280;">
            &larr; Back to Gift Cards
        </a>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">Issue Gift Card</span>
        </div>

        @if($errors->any())
        <div style="background: #fee2e2; border: 1px solid #fca5a5; padding: 0.875rem 1rem; margin-bottom: 1.25rem; border-radius: 0.25rem;">
            <p style="font-size: 0.8125rem; font-weight: 600; color: #991b1b; margin-bottom: 0.375rem;">Please fix the following errors:</p>
            <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.8125rem; color: #991b1b;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.gift-cards.store') }}">
            @csrf

            {{-- Amount --}}
            <div style="margin-bottom: 1.25rem;">
                <label class="admin-label" for="amount">Starting Balance <span style="color: #ba1a1a;">*</span></label>
                <div style="position: relative; max-width: 200px;">
                    <span style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 0.875rem; pointer-events: none;">$</span>
                    <input type="number" id="amount" name="amount" class="admin-input"
                        value="{{ old('amount') }}" step="0.01" min="0.01" placeholder="0.00"
                        style="padding-left: 1.75rem;" required>
                </div>
                <p class="admin-hint">A unique code is generated automatically.</p>
                @error('amount') <p class="admin-error-text">{{ $message }}</p> @enderror
            </div>

            {{-- Recipient --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label class="admin-label" for="recipient_name">Recipient Name <span style="color: #9ca3af; font-weight: 400;">(optional)</span></label>
                    <input type="text" id="recipient_name" name="recipient_name" class="admin-input" value="{{ old('recipient_name') }}">
                    @error('recipient_name') <p class="admin-error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label" for="recipient_email">Recipient Email <span style="color: #9ca3af; font-weight: 400;">(optional)</span></label>
                    <input type="email" id="recipient_email" name="recipient_email" class="admin-input" value="{{ old('recipient_email') }}">
                    @error('recipient_email') <p class="admin-error-text">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Purchaser & Expiry --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label class="admin-label" for="purchaser_email">Purchaser Email <span style="color: #9ca3af; font-weight: 400;">(optional)</span></label>
                    <input type="email" id="purchaser_email" name="purchaser_email" class="admin-input" value="{{ old('purchaser_email') }}">
                    @error('purchaser_email') <p class="admin-error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label" for="expires_at">Expires At <span style="color: #9ca3af; font-weight: 400;">(optional)</span></label>
                    <input type="date" id="expires_at" name="expires_at" class="admin-input" value="{{ old('expires_at') }}">
                    <p class="admin-hint">Leave blank for no expiry.</p>
                    @error('expires_at') <p class="admin-error-text">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Message --}}
            <div style="margin-bottom: 1.5rem;">
                <label class="admin-label" for="message">Gift Message <span style="color: #9ca3af; font-weight: 400;">(optional)</span></label>
                <textarea id="message" name="message" class="admin-input" rows="3">{{ old('message') }}</textarea>
                @error('message') <p class="admin-error-text">{{ $message }}</p> @enderror
            </div>

            <div style="display: flex; gap: 0.75rem; padding-top: 0.5rem; border-top: 1px solid #f3f4f6;">
                <button type="submit" class="admin-btn admin-btn-primary">Issue Gift Card</button>
                <a href="{{ route('admin.gift-cards.index') }}" class="admin-btn admin-btn-outline">Cancel</a>
            </div>
        </form>
    </div>

</div>

@endsection
