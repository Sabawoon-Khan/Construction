<!DOCTYPE html>
<html
        lang="{{ str_replace('_', '-', app()->getLocale()) }}"
        dir="{{ app(\App\Services\LocaleRegistry::class)->direction(app()->getLocale()) }}"
        @class([
            'dark' => ($appearance ?? 'system') == 'dark',
            'glass' => ($interfaceStyle ?? 'standard') == 'glass',
        ])
    >
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }

                try {
                    const match = document.cookie.match(/(?:^|; )locale=([^;]+)/);
                    const locale = match ? decodeURIComponent(match[1]) : null;
                    const dirs = @json(app(\App\Services\LocaleRegistry::class)->directions());
                    if (locale && dirs[locale]) {
                        document.documentElement.lang = locale;
                        document.documentElement.dir = dirs[locale];
                    }
                } catch (e) {}
            })();
        </script>

        {{-- Apply org theme colors before paint (from org_settings) --}}
        <script>
            (function() {
                try {
                    var c = @json($themeColors ?? \App\Support\ThemeColors::defaults());
                    var root = document.documentElement;
                    var dark = root.classList.contains('dark');
                    var accent = dark ? (c.accentDark || c.accent) : c.accent;
                    var bg = dark ? (c.backgroundDark || '#0b1220') : (c.backgroundLight || '#f1f4f9');
                    var radiusMap = { soft: '1.125rem', medium: '0.875rem', sharp: '0.375rem' };

                    if (c.brand) {
                        root.style.setProperty('--school-navy', c.brand);
                        root.style.setProperty('--kit-brand', c.brand);
                        root.style.setProperty('--sidebar-background', c.brand);
                        root.style.setProperty('--sidebar', c.brand);
                    }
                    if (c.secondary) {
                        root.style.setProperty('--school-gold', c.secondary);
                    }
                    if (accent) {
                        root.style.setProperty('--primary', accent);
                        root.style.setProperty('--brand-accent', accent);
                        root.style.setProperty('--school-accent', accent);
                        root.style.setProperty('--kit-accent', accent);
                        root.style.setProperty('--sidebar-primary', accent);
                    }
                    if (bg) {
                        root.style.setProperty('--background', bg);
                        root.style.setProperty('--school-panel', bg);
                        root.style.setProperty('--app-shell', bg);
                        root.style.setProperty('--kit-panel', bg);
                    }
                    if (c.radius && radiusMap[c.radius]) {
                        root.style.setProperty('--radius', radiusMap[c.radius]);
                        root.setAttribute('data-theme-radius', c.radius);
                    }
                    if (c.density) {
                        root.setAttribute('data-theme-density', c.density);
                    }
                    if (c.sidebar_active) {
                        root.setAttribute('data-theme-sidebar-active', c.sidebar_active);
                    }
                    if (c.shell) {
                        root.setAttribute('data-theme-shell', c.shell);
                    }
                } catch (e) {}
            })();
        </script>

        <style>
            html {
                background-color: var(--background, #f1f4f9);
            }

            html.dark {
                background-color: var(--background, #0b1220);
            }
        </style>

        <link rel="icon" href="{{ \App\Models\OrgSetting::faviconUrl() ?? '/favicon.ico' }}" sizes="any">
        @if (! \App\Models\OrgSetting::faviconUrl())
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
            <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @endif
        @if (\App\Support\OfflineSettings::pwaEnabled())
            <link rel="manifest" href="/manifest.webmanifest">
            <meta name="theme-color" content="#162238">
        @endif
        <meta
            name="kit-offline"
            content="{{ \App\Support\OfflineSettings::enabled() ? '1' : '0' }}"
            data-pwa="{{ \App\Support\OfflineSettings::pwaEnabled() ? '1' : '0' }}"
            data-auto-sync="{{ \App\Support\OfflineSettings::autoSync() ? '1' : '0' }}"
            data-warm="{{ \App\Support\OfflineSettings::warmCache() ? '1' : '0' }}"
        >

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
