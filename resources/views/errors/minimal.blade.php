<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light dark">
        <title>@yield('title') — {{ config('app.name', 'Laravel') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=Instrument+Sans:400,500,600&display=swap" rel="stylesheet">
        <style>
            :root {
                --bg: hsl(210 20% 98%);
                --fg: hsl(222 47% 11%);
                --muted: hsl(215 16% 47%);
                --card: hsl(0 0% 100%);
                --border: hsl(214 20% 91%);
                --accent: hsl(221 83% 53%);
                --accent-fg: hsl(0 0% 100%);
                --radius: 0.75rem;
            }
            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: hsl(222 47% 11%);
                    --fg: hsl(210 40% 98%);
                    --muted: hsl(215 20% 65%);
                    --card: hsl(222 40% 14%);
                    --border: hsl(222 30% 22%);
                    --accent: hsl(217 91% 60%);
                    --accent-fg: hsl(222 47% 11%);
                }
            }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                background: var(--bg);
                color: var(--fg);
                -webkit-font-smoothing: antialiased;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
            }
            .panel {
                width: 100%;
                max-width: 28rem;
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: var(--radius);
                padding: 2rem 1.75rem;
                text-align: center;
                box-shadow: 0 12px 40px rgb(15 23 42 / 0.08);
            }
            @media (prefers-color-scheme: dark) {
                .panel {
                    box-shadow: 0 12px 48px rgb(0 0 0 / 0.35);
                }
            }
            .code {
                font-size: 3.5rem;
                font-weight: 600;
                line-height: 1;
                letter-spacing: -0.04em;
                color: var(--muted);
                margin: 0 0 0.75rem;
            }
            .message {
                font-size: 1.125rem;
                font-weight: 500;
                margin: 0 0 1.5rem;
                line-height: 1.45;
            }
            .hint {
                font-size: 0.875rem;
                color: var(--muted);
                margin: 0 0 1.5rem;
                line-height: 1.5;
            }
            a.btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0.5rem 1.25rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: var(--accent-fg);
                background: var(--accent);
                border-radius: calc(var(--radius) - 2px);
                text-decoration: none;
                transition: opacity 0.15s ease;
            }
            a.btn:hover { opacity: 0.92; }
            a.btn:focus-visible {
                outline: 2px solid var(--accent);
                outline-offset: 2px;
            }
        </style>
    </head>
    <body>
        <main class="panel" role="main">
            <p class="code" aria-hidden="true">@yield('code')</p>
            <h1 class="message">@yield('message')</h1>
            <p class="hint">{{ __('If this keeps happening, contact support or try again in a few minutes.') }}</p>
            <a class="btn" href="{{ url('/') }}">{{ __('Back to home') }}</a>
        </main>
    </body>
</html>
