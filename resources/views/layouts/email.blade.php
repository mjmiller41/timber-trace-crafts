<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('subject', 'Timber Trace Crafts')</title>
    <style>
        /* Email client reset */
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            background-color: #F4F1EA;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        body {
            font-family: 'Montserrat', Helvetica, Arial, sans-serif;
            color: #333333;
            background-color: #F4F1EA;
        }
        table {
            border-spacing: 0;
            border-collapse: collapse;
        }
        td {
            padding: 0;
        }
        img {
            border: 0;
            display: block;
            max-width: 100%;
        }
        a {
            color: #2C4C3B;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }

        /* Layout */
        .email-wrapper {
            width: 100%;
            background-color: #F4F1EA;
            padding: 32px 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        /* Header */
        .email-header {
            background-color: #2C4C3B;
            padding: 28px 40px;
            text-align: center;
        }
        .email-logo {
            font-family: Georgia, 'Times New Roman', Times, serif;
            font-size: 22px;
            font-weight: 300;
            color: #F4F1EA;
            letter-spacing: 0.04em;
            margin: 0;
        }
        .email-logo-tagline {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: rgba(244, 241, 234, 0.65);
            margin: 6px 0 0;
        }

        /* Content */
        .email-body {
            padding: 40px 40px 32px;
        }
        .email-body h1 {
            font-family: Georgia, 'Times New Roman', Times, serif;
            font-size: 26px;
            font-weight: 300;
            color: #333333;
            margin: 0 0 16px;
            line-height: 1.3;
        }
        .email-body h2 {
            font-family: Georgia, 'Times New Roman', Times, serif;
            font-size: 18px;
            font-weight: 300;
            color: #333333;
            margin: 28px 0 10px;
            line-height: 1.3;
        }
        .email-body p {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.7;
            color: #555555;
            margin: 0 0 16px;
        }
        .email-body strong {
            color: #333333;
        }
        .email-divider {
            border: none;
            border-top: 1px solid #E8E2D9;
            margin: 24px 0;
        }

        /* Button */
        .email-btn {
            display: inline-block;
            background-color: #333333;
            color: #F4F1EA !important;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            padding: 14px 28px;
            text-decoration: none !important;
        }
        .email-btn-forest {
            background-color: #2C4C3B;
        }

        /* Order table */
        .email-table {
            width: 100%;
            border-collapse: collapse;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 13px;
        }
        .email-table th {
            text-align: left;
            padding: 8px 12px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #8C7B6C;
            border-bottom: 2px solid #E8E2D9;
        }
        .email-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #F4F1EA;
            color: #555555;
        }
        .email-table tfoot td {
            font-weight: 700;
            color: #333333;
            border-top: 2px solid #E8E2D9;
            border-bottom: none;
        }

        /* Footer */
        .email-footer {
            background-color: #F4F1EA;
            padding: 24px 40px;
            text-align: center;
            border-top: 1px solid #E8E2D9;
        }
        .email-footer p {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #8C7B6C;
            margin: 4px 0;
            line-height: 1.6;
        }
        .email-footer a {
            color: #8C7B6C;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }
            .email-body {
                padding: 28px 24px 24px !important;
            }
            .email-header {
                padding: 24px !important;
            }
            .email-footer {
                padding: 20px 24px !important;
            }
        }
    </style>
</head>
<body>

    <div class="email-wrapper">
        <table class="email-container" align="center" width="600" cellpadding="0" cellspacing="0">

            {{-- Header / Logo --}}
            <tr>
                <td class="email-header">
                    <p class="email-logo">Timber Trace Crafts</p>
                    <p class="email-logo-tagline">Handcrafted with precision</p>
                </td>
            </tr>

            {{-- Content --}}
            <tr>
                <td class="email-body">
                    @yield('content')
                </td>
            </tr>

            {{-- Footer --}}
            <tr>
                <td class="email-footer">
                    <p>&copy; {{ date('Y') }} Timber Trace Crafts &nbsp;|&nbsp; <a href="https://timbertracecrafts.com">timbertracecrafts.com</a></p>
                    <p>
                        <a href="{{ url('/privacy-policy') }}">Privacy Policy</a>
                        &nbsp;&middot;&nbsp;
                        <a href="{{ url('/terms') }}">Terms &amp; Conditions</a>
                        &nbsp;&middot;&nbsp;
                        <a href="{{ url('/returns') }}">Return Policy</a>
                    </p>
                    <p>You are receiving this email because you placed an order with us.</p>
                </td>
            </tr>

        </table>
    </div>

</body>
</html>
