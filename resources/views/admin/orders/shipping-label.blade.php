@php
    $from = config('admin.ship_from');
    $hasFrom = ! empty($from['line1']) || ! empty($from['city']);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shipping Label — Order #{{ $order->id }} | {{ $siteName ?? 'Timber Trace Crafts' }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #000;
            background: #e5e5e5;
        }

        /* Avery 5126: US-Letter sheet, two 8.5in x 5.5in labels stacked. */
        @page { size: letter portrait; margin: 0; }

        .sheet {
            width: 8.5in;
            height: 11in;
            margin: 0 auto;
            background: #fff;
        }

        .label {
            width: 8.5in;
            height: 5.5in;
            padding: 0.5in;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Perforation guide between the two labels — screen only. */
        .label--top { border-bottom: 1px dashed #bbb; }

        .from {
            font-size: 11pt;
            line-height: 1.35;
            max-width: 60%;
        }

        .from .store {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .meta {
            margin-left: auto;
            text-align: right;
            font-size: 10pt;
            color: #333;
        }

        .top-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .to {
            margin: auto 0;
            padding-left: 0.4in;
        }

        .to .to-label {
            font-size: 10pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #444;
            margin-bottom: 6px;
        }

        .to address {
            font-style: normal;
            font-size: 22pt;
            font-weight: 700;
            line-height: 1.28;
        }

        .to .country {
            font-size: 16pt;
            font-weight: 700;
            margin-top: 4px;
        }

        .toolbar {
            max-width: 8.5in;
            margin: 12px auto;
            text-align: center;
        }

        .toolbar button {
            background: #1a1a1a; color: #fff; border: none;
            padding: 8px 22px; font-size: 12px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.08em; cursor: pointer;
        }

        .toolbar span { display: block; margin-top: 6px; font-size: 11px; color: #555; }

        @media print {
            html, body { background: #fff; }
            .toolbar { display: none; }
            .sheet { margin: 0; }
            .label--top { border-bottom: none; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button onclick="window.print()">Print / Save PDF</button>
    <span>Avery 5126 &middot; 8.5&Prime; &times; 5.5&Prime; labels, 2 per sheet &middot; prints to the top label</span>
</div>

<div class="sheet">
    {{-- Top label — the shipping label for this order --}}
    <div class="label label--top">
        <div class="top-row">
            @if($hasFrom)
                <div class="from">
                    <div class="store">{{ $siteName ?? 'Timber Trace Crafts' }}</div>
                    @if($from['line1'])<div>{{ $from['line1'] }}</div>@endif
                    @if($from['line2'])<div>{{ $from['line2'] }}</div>@endif
                    <div>{{ trim(($from['city'] ?? '').(($from['city'] && $from['state']) ? ', ' : '').($from['state'] ?? '').' '.($from['zip'] ?? '')) }}</div>
                </div>
            @else
                <div class="from"><div class="store">{{ $siteName ?? 'Timber Trace Crafts' }}</div></div>
            @endif

            <div class="meta">
                <div><strong>Order #{{ $order->id }}</strong></div>
                <div>{{ $order->created_at?->format('M j, Y') ?? '' }}</div>
            </div>
        </div>

        <div class="to">
            <div class="to-label">Ship To</div>
            <address>
                {{ $order->shipping_first_name }} {{ $order->shipping_last_name }}<br>
                {{ $order->shipping_line1 }}
                @if($order->shipping_line2)<br>{{ $order->shipping_line2 }}@endif
                <br>{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}
                @if($order->shipping_country && $order->shipping_country !== 'US')
                    <div class="country">{{ $order->shipping_country }}</div>
                @endif
            </address>
        </div>
    </div>

    {{-- Bottom label left blank so a single sheet yields one clean label. --}}
    <div class="label label--bottom"></div>
</div>

</body>
</html>
