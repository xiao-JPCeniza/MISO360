<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ $appName }} Email Verification</title>
    <style>
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }

        body {
            margin: 0;
            padding: 24px 12px;
            background: #f1f5f9;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: #0f172a;
        }

        .email-shell {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #d8e2ef;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.08);
        }

        .header {
            padding: 24px;
            background: linear-gradient(120deg, #2563eb 0%, #1d4ed8 100%);
            text-align: center;
        }

        .logo {
            max-height: 52px;
            width: auto;
        }

        .content {
            padding: 28px 28px 22px;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 24px;
            line-height: 1.25;
            color: #0f172a;
        }

        p {
            margin: 0 0 14px;
            line-height: 1.6;
            color: #334155;
        }

        .cta-wrap {
            text-align: center;
            margin: 22px 0;
        }

        .cta {
            display: inline-block;
            background: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 700;
            border-radius: 10px;
            padding: 12px 24px;
        }

        .meta {
            margin-top: 16px;
            border-radius: 12px;
            border: 1px solid #dbe6f3;
            background: #f8fafc;
            padding: 14px 16px;
            font-size: 13px;
            color: #475569;
        }

        .meta-row {
            margin-bottom: 8px;
        }

        .meta-row:last-child {
            margin-bottom: 0;
        }

        .footer {
            padding: 18px 28px 26px;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }

        .support {
            color: #2563eb;
            text-decoration: none;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: #0f172a !important;
                color: #e2e8f0 !important;
            }

            .email-shell {
                background: #162238 !important;
                border-color: #24334e !important;
                box-shadow: 0 14px 48px rgba(2, 6, 23, 0.55) !important;
            }

            h1 {
                color: #f8fafc !important;
            }

            p {
                color: #cbd5e1 !important;
            }

            .cta {
                background: #60a5fa !important;
                color: #0f172a !important;
            }

            .meta {
                background: #0b1220 !important;
                border-color: #2e3c58 !important;
                color: #cbd5e1 !important;
            }

            .footer {
                color: #94a3b8 !important;
                border-top-color: #2e3c58 !important;
            }

            .support {
                color: #93c5fd !important;
            }
        }
    </style>
</head>
<body>
    <div class="email-shell">
        <div class="header">
            <img class="logo" src="{{ $logoUrl }}" alt="{{ $appName }} logo">
        </div>

        <div class="content">
            <h1>Verify Your Email Address</h1>
            <p>Thanks for creating your <strong>{{ $appName }}</strong> account. Please confirm your email address to continue.</p>

            <div class="cta-wrap">
                <a class="cta" href="{{ $verificationUrl }}">Verify Email Address</a>
            </div>

            <p>If the button does not work, copy and paste this URL into your browser:</p>
            <p style="word-break: break-word; font-size: 12px;">{{ $verificationUrl }}</p>

            <div class="meta">
                <div class="meta-row"><strong>Link Sent At:</strong> {{ $sentAtPht }} {{ $timezoneLabel }}</div>
                <div class="meta-row"><strong>Expiration Time:</strong> {{ $expiresAtPht }} {{ $timezoneLabel }}</div>
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0 0 8px;">Need support? Reply to this email or contact <a class="support" href="mailto:{{ $replyToAddress }}">{{ $replyToAddress }}</a>.</p>
            <p style="margin: 0;">This verification link is valid for {{ $validMinutes }} minutes based on the local server clock ({{ $timezoneLabel }}).</p>
        </div>
    </div>
</body>
</html>
