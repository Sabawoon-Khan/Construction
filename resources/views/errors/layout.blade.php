<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app(\App\Services\LocaleRegistry::class)->direction(app()->getLocale()) }}"
    @class([
        'dark' => ($appearance ?? 'system') == 'dark',
    ])
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script>
        (function() {
            const appearance = '{{ $appearance ?? "system" }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    <style>
        :root {
            --background: #f1f4f9;
            --foreground: #111827;
            --muted: #6b7280;
            --primary: #162238;
            --accent: #10b981;
        }

        html.dark {
            --background: #0b1220;
            --foreground: #f3f4f6;
            --muted: #9ca3af;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--background);
            color: var(--foreground);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
            line-height: 1.5;
        }

        .error-card {
            max-width: 28rem;
            width: 100%;
            text-align: center;
        }

        .error-code {
            font-size: 6rem;
            font-weight: 800;
            line-height: 1;
            color: var(--accent);
            letter-spacing: -0.05em;
        }

        .error-title {
            margin-top: 1rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .error-message {
            margin-top: 0.75rem;
            color: var(--muted);
        }

        .error-actions {
            margin-top: 2rem;
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: opacity 0.15s ease;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-outline {
            border: 1px solid #d1d5db;
            color: var(--foreground);
        }

        html.dark .btn-outline {
            border-color: #374151;
        }
    </style>

    <link rel="icon" href="{{ \App\Models\OrgSetting::faviconUrl() ?? '/favicon.png' }}" type="image/png" sizes="any">

    <title>@yield('title', config('app.name', 'Laravel'))</title>
</head>
<body>
    <div class="error-card">
        @yield('content')
    </div>
</body>
</html>
