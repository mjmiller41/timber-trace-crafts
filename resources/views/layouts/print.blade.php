<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Packing Slip') | {{ $siteName ?? 'Timber Trace Crafts' }}</title>
    <style>
        /* Base reset */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #1a1a1a;
            background: #ffffff;
            line-height: 1.5;
        }

        /* Page layout */
        .page {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 48px;
        }

        /* Header */
        .print-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .print-logo {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 20px;
            font-weight: normal;
            letter-spacing: 0.02em;
            color: #1a1a1a;
        }

        .print-logo-sub {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #777777;
            margin-top: 3px;
        }

        .print-doc-title {
            text-align: right;
        }

        .print-doc-title h1 {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #1a1a1a;
        }

        .print-doc-title .doc-meta {
            font-size: 11px;
            color: #555555;
            margin-top: 4px;
        }

        /* Address block */
        .print-addresses {
            display: flex;
            gap: 48px;
            margin-bottom: 28px;
        }

        .print-address-block {
            flex: 1;
        }

        .print-section-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #8C7B6C;
            margin-bottom: 6px;
            border-bottom: 1px solid #e0dbd4;
            padding-bottom: 4px;
        }

        .print-address-block address {
            font-style: normal;
            font-size: 12px;
            line-height: 1.7;
            color: #1a1a1a;
        }

        /* Order details table */
        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .print-table thead tr {
            border-bottom: 2px solid #1a1a1a;
        }

        .print-table th {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #555555;
            padding: 6px 8px;
            text-align: left;
        }

        .print-table th:last-child,
        .print-table td:last-child {
            text-align: right;
        }

        .print-table td {
            padding: 8px 8px;
            font-size: 12px;
            color: #1a1a1a;
            border-bottom: 1px solid #e8e2d9;
            vertical-align: top;
        }

        .print-table .product-name {
            font-weight: 600;
        }

        .print-table .product-variant {
            font-size: 11px;
            color: #777777;
            margin-top: 2px;
        }

        /* Totals */
        .print-totals {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 28px;
        }

        .print-totals-inner {
            width: 240px;
        }

        .print-totals-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            padding: 4px 0;
            color: #333333;
        }

        .print-totals-row.total {
            font-size: 14px;
            font-weight: 700;
            border-top: 2px solid #1a1a1a;
            padding-top: 8px;
            margin-top: 4px;
            color: #1a1a1a;
        }

        /* Notes */
        .print-notes {
            border: 1px solid #e8e2d9;
            padding: 12px 16px;
            font-size: 11px;
            color: #555555;
            margin-bottom: 28px;
        }

        .print-notes-label {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 9px;
            color: #8C7B6C;
            margin-bottom: 4px;
        }

        /* Footer */
        .print-footer {
            border-top: 1px solid #e8e2d9;
            padding-top: 16px;
            text-align: center;
            font-size: 10px;
            color: #8C7B6C;
        }

        /* Print-specific styles */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                font-size: 12pt;
            }

            .page {
                margin: 0;
                padding: 0;
                max-width: none;
            }

            .no-print {
                display: none !important;
            }

            a {
                text-decoration: none;
                color: inherit;
            }

            table {
                page-break-inside: avoid;
            }

            .print-header {
                margin-bottom: 20pt;
            }
        }
    </style>
</head>
<body>

    {{-- Print button (hidden when actually printing) --}}
    <div class="no-print" style="position:fixed;top:16px;right:16px;z-index:100;">
        <button
            onclick="window.print()"
            style="background:#1a1a1a;color:#ffffff;border:none;padding:8px 20px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;cursor:pointer;"
        >
            Print / Save PDF
        </button>
    </div>

    <div class="page">
        @yield('content')
    </div>

</body>
</html>
