<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <style>
        :root {
            color-scheme: light;
            --navy: #162238;
            --navy-muted: #2a3a55;
            --border: #d8dee8;
            --text: #111827;
            --muted: #5b6472;
        }

        * {
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 14mm 12mm 16mm;
        }

        body {
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            margin: 0;
            padding: 1.25rem 1.5rem 2rem;
            color: var(--text);
            line-height: 1.45;
            font-size: 11pt;
            background: #f8fafc;
        }

        .sheet {
            max-width: 210mm;
            margin: 0 auto;
            background: #fff;
            min-height: 100%;
        }

        .letterhead {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1.5rem;
            padding-bottom: 0.85rem;
            margin-bottom: 1rem;
            border-bottom: 3px solid var(--navy);
        }

        .letterhead-brand {
            flex: 1;
        }

        .letterhead-company {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--navy);
        }

        .letterhead-brand-line {
            margin: 0.2rem 0 0;
            font-size: 0.8rem;
            color: var(--muted);
        }

        .letterhead-meta {
            text-align: end;
            min-width: 9rem;
        }

        .letterhead-doc {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--navy);
        }

        .letterhead-meta-line {
            margin: 0.25rem 0 0;
            font-size: 0.75rem;
            color: var(--muted);
        }

        .toolbar {
            margin-bottom: 1rem;
        }

        .toolbar button {
            font: inherit;
            font-size: 0.875rem;
            padding: 0.45rem 0.85rem;
            border: 1px solid var(--border);
            border-radius: 0.375rem;
            background: #fff;
            cursor: pointer;
            color: var(--navy);
        }

        .toolbar button:hover {
            background: #f1f5f9;
        }

        .content h1,
        .content h2 {
            color: var(--navy);
            letter-spacing: -0.02em;
        }

        .content h1 {
            font-size: 1.1rem;
            margin: 0 0 0.5rem;
        }

        .content h2 {
            font-size: 0.95rem;
            margin: 1.25rem 0 0.5rem;
        }

        .meta {
            color: var(--muted);
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }

        th,
        td {
            border: 1px solid var(--border);
            padding: 0.4rem 0.55rem;
            text-align: start;
        }

        th {
            background: #eef2f7;
            color: var(--navy-muted);
            font-weight: 600;
        }

        .sheet-footer {
            margin-top: 2rem;
            padding-top: 0.65rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.7rem;
            color: var(--muted);
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .sheet {
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <header class="letterhead">
            <div class="letterhead-brand">
                <p class="letterhead-company">{{ config('app.name') }}</p>
                @hasSection('brand')
                    <p class="letterhead-brand-line">@yield('brand')</p>
                @endif
            </div>
            <div class="letterhead-meta">
                <p class="letterhead-doc">@yield('title', config('app.name'))</p>
                @hasSection('meta')
                    <p class="letterhead-meta-line">@yield('meta')</p>
                @endif
                <p class="letterhead-meta-line">{{ __('Printed') }} {{ now()->format('Y-m-d H:i') }}</p>
            </div>
        </header>

        <div class="toolbar no-print">
            <button type="button" onclick="window.print()">{{ __('Print') }}</button>
        </div>

        <div class="content">
            @yield('content')
        </div>

        <footer class="sheet-footer">
            <span>{{ config('app.name') }}</span>
            <span>{{ __('Confidential') }} · © {{ date('Y') }}</span>
        </footer>
    </div>
</body>
</html>
