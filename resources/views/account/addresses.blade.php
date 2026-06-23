@extends('layouts.app')

@section('title', 'Saved Addresses')

@section('content')
<div class="page-container py-12">

    <div class="mb-8 pb-6 border-b border-walnut/20">
        <p class="section-label mb-2">Account</p>
        <h1 class="font-heading text-3xl font-light">Saved Addresses</h1>
    </div>

    <div class="flex flex-col lg:flex-row gap-10">

        @include('account.partials.sidebar')

        <div class="flex-1 min-w-0" x-data="{ showAddForm: false, editId: null }">

            @if($errors->any())
                <div class="mb-6 border border-error p-4" style="background-color: #fee2e2;">
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="text-sm" style="color: #991b1b;">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Address List --}}
            @if($addresses->isEmpty())
                <div class="card p-8 text-center mb-6">
                    <p class="text-sm text-walnut">No saved addresses yet. Add one below.</p>
                </div>
            @else
                <div class="space-y-4 mb-8">
                    @foreach($addresses as $address)
                        <div class="card p-5" x-data="{ editing: false }">

                            {{-- View Mode --}}
                            <div x-show="!editing">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        @if($address->label)
                                            <p class="section-label mb-2">{{ $address->label }}</p>
                                        @endif
                                        <address class="text-sm not-italic leading-relaxed">
                                            <strong>{{ $address->first_name }} {{ $address->last_name }}</strong><br>
                                            {{ $address->address_line1 }}<br>
                                            @if($address->address_line2)
                                                {{ $address->address_line2 }}<br>
                                            @endif
                                            {{ $address->city }}, {{ $address->state }} {{ $address->zip }}<br>
                                            {{ $address->country }}
                                            @if($address->phone)
                                                <br>{{ $address->phone }}
                                            @endif
                                        </address>
                                        @if($address->is_default)
                                            <span class="admin-badge-success mt-2 inline-flex">Default</span>
                                        @endif
                                    </div>
                                    <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                                        <button
                                            type="button"
                                            @click="editing = true"
                                            class="text-xs font-semibold uppercase tracking-widest text-walnut hover:text-charcoal transition-colors"
                                        >
                                            Edit
                                        </button>
                                        <form
                                            method="POST"
                                            action="{{ route('account.addresses.delete', $address) }}"
                                            onsubmit="return confirm('Delete this address?')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold uppercase tracking-widest hover:text-charcoal transition-colors" style="color: #991b1b;">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Edit Mode --}}
                            <div x-show="editing" x-cloak>
                                <p class="section-label mb-4">Edit Address</p>
                                <form method="POST" action="{{ route('account.addresses.update', $address) }}">
                                    @csrf
                                    @method('PUT')

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">First Name <span class="text-error">*</span></label>
                                            <input type="text" name="first_name" class="form-field" value="{{ old('first_name', $address->first_name) }}" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">Last Name <span class="text-error">*</span></label>
                                            <input type="text" name="last_name" class="form-field" value="{{ old('last_name', $address->last_name) }}" required>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">Label <span class="text-walnut font-normal normal-case tracking-normal">(optional, e.g. Home)</span></label>
                                            <input type="text" name="label" class="form-field" value="{{ old('label', $address->label) }}" placeholder="Home, Work…">
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">Address Line 1 <span class="text-error">*</span></label>
                                            <input type="text" name="address_line1" class="form-field" value="{{ old('address_line1', $address->address_line1) }}" required>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">Address Line 2</label>
                                            <input type="text" name="address_line2" class="form-field" value="{{ old('address_line2', $address->address_line2) }}">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">City <span class="text-error">*</span></label>
                                            <input type="text" name="city" class="form-field" value="{{ old('city', $address->city) }}" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">State <span class="text-error">*</span></label>
                                            <select name="state" class="form-field" required>
                                                @include('account.partials.state-options', ['selected' => old('state', $address->state)])
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">ZIP Code <span class="text-error">*</span></label>
                                            <input type="text" name="zip" class="form-field" value="{{ old('zip', $address->zip) }}" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">Country <span class="text-error">*</span></label>
                                            <input type="text" name="country" class="form-field" value="{{ old('country', $address->country ?? 'US') }}" maxlength="2" required>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">Phone</label>
                                            <input type="tel" name="phone" class="form-field" value="{{ old('phone', $address->phone) }}">
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    name="is_default"
                                                    value="1"
                                                    style="accent-color: #333333; width: 1rem; height: 1rem;"
                                                    {{ old('is_default', $address->is_default) ? 'checked' : '' }}
                                                >
                                                <span class="text-sm">Set as default address</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 mt-5">
                                        <button type="submit" class="btn-primary text-sm">Update Address</button>
                                        <button type="button" @click="editing = false" class="btn-outline text-sm">Cancel</button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Add New Address Button --}}
            <div>
                <button
                    type="button"
                    @click="showAddForm = !showAddForm"
                    class="btn-outline"
                    x-text="showAddForm ? 'Cancel' : 'Add New Address'"
                ></button>
            </div>

            {{-- Add New Address Form --}}
            <div x-show="showAddForm" x-cloak class="mt-6 card p-6">
                <p class="section-label mb-5">New Address</p>
                <form method="POST" action="{{ route('account.addresses.store') }}">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="new_first_name" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">First Name <span class="text-error">*</span></label>
                            <input type="text" id="new_first_name" name="first_name" class="form-field" value="{{ old('first_name') }}" required>
                        </div>
                        <div>
                            <label for="new_last_name" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">Last Name <span class="text-error">*</span></label>
                            <input type="text" id="new_last_name" name="last_name" class="form-field" value="{{ old('last_name') }}" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="new_label" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">Label <span class="text-walnut font-normal normal-case tracking-normal">(optional, e.g. Home)</span></label>
                            <input type="text" id="new_label" name="label" class="form-field" value="{{ old('label') }}" placeholder="Home, Work…">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="new_address_line1" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">Address Line 1 <span class="text-error">*</span></label>
                            <input type="text" id="new_address_line1" name="address_line1" class="form-field" value="{{ old('address_line1') }}" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="new_address_line2" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">Address Line 2</label>
                            <input type="text" id="new_address_line2" name="address_line2" class="form-field" value="{{ old('address_line2') }}">
                        </div>
                        <div>
                            <label for="new_city" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">City <span class="text-error">*</span></label>
                            <input type="text" id="new_city" name="city" class="form-field" value="{{ old('city') }}" required>
                        </div>
                        <div>
                            <label for="new_state" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">State <span class="text-error">*</span></label>
                            <select id="new_state" name="state" class="form-field" required>
                                <option value="">Select state…</option>
                                @php
                                    $states = ['AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas','CA'=>'California','CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware','FL'=>'Florida','GA'=>'Georgia','HI'=>'Hawaii','ID'=>'Idaho','IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa','KS'=>'Kansas','KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland','MA'=>'Massachusetts','MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi','MO'=>'Missouri','MT'=>'Montana','NE'=>'Nebraska','NV'=>'Nevada','NH'=>'New Hampshire','NJ'=>'New Jersey','NM'=>'New Mexico','NY'=>'New York','NC'=>'North Carolina','ND'=>'North Dakota','OH'=>'Ohio','OK'=>'Oklahoma','OR'=>'Oregon','PA'=>'Pennsylvania','RI'=>'Rhode Island','SC'=>'South Carolina','SD'=>'South Dakota','TN'=>'Tennessee','TX'=>'Texas','UT'=>'Utah','VT'=>'Vermont','VA'=>'Virginia','WA'=>'Washington','WV'=>'West Virginia','WI'=>'Wisconsin','WY'=>'Wyoming','DC'=>'Washington D.C.'];
                                @endphp
                                @foreach($states as $code => $label)
                                    <option value="{{ $code }}" {{ old('state') === $code ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="new_zip" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">ZIP Code <span class="text-error">*</span></label>
                            <input type="text" id="new_zip" name="zip" class="form-field" value="{{ old('zip') }}" required>
                        </div>
                        <div>
                            <label for="new_country" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">Country <span class="text-error">*</span></label>
                            <input type="text" id="new_country" name="country" class="form-field" value="{{ old('country', 'US') }}" maxlength="2" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="new_phone" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">Phone</label>
                            <input type="tel" id="new_phone" name="phone" class="form-field" value="{{ old('phone') }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="is_default"
                                    value="1"
                                    style="accent-color: #333333; width: 1rem; height: 1rem;"
                                    {{ old('is_default') ? 'checked' : '' }}
                                >
                                <span class="text-sm">Set as default address</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-5">
                        <button type="submit" class="btn-primary">Save Address</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection
