<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <style>
        :root {
            color-scheme: light;
            --brand: #0d9488;
            --brand-dark: #0f766e;
            --brand-ink: #115e59;
            --brand-soft: #99f6e4;
            --brand-pale: #f0fdfa;
            --brand-panel: #ccfbf1;
            --accent: #0284c7;
            --accent-deep: #0369a1;
            --text: #134e4a;
            --muted: #0f766e;
            --line: #5eead4;
            --row-alt: #ecfdf5;
        }

        * {
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 12mm 12mm 14mm;
        }

        body {
            font-family: system-ui, -apple-system, "Segoe UI", "Noto Sans Arabic", sans-serif;
            margin: 0;
            padding: 1rem 1.25rem 1.5rem;
            color: var(--text);
            line-height: 1.45;
            font-size: 11pt;
            background: #e2e8f0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .sheet {
            position: relative;
            max-width: 210mm;
            margin: 0 auto;
            background: #fff;
            min-height: 100%;
            padding: 0.5rem 0.65rem 0.85rem;
            border: 1px solid var(--line);
            outline: 2px solid var(--brand);
            outline-offset: 3px;
            border-radius: 4px;
            overflow: hidden;
        }

        .sheet::before {
            content: '';
            display: block;
            height: 5px;
            margin: -0.5rem -0.65rem 0.65rem;
            background: linear-gradient(90deg, var(--brand-dark), var(--brand), var(--accent));
        }

        .letterhead {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1.5rem;
            padding: 0.35rem 0.55rem 0.65rem;
            margin-bottom: 0.85rem;
            border-bottom: 2px solid var(--brand);
            background: linear-gradient(180deg, var(--brand-pale) 0%, #fff 78%);
        }

        .letterhead-brand {
            flex: 1;
        }

        .letterhead-company {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--brand-ink);
        }

        .letterhead-brand-line {
            margin: 0.25rem 0 0;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--muted);
        }

        .letterhead-meta {
            text-align: end;
            min-width: 9rem;
        }

        .letterhead-doc {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--accent-deep);
        }

        .letterhead-meta-line {
            margin: 0.25rem 0 0;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--muted);
        }

        .toolbar {
            margin-bottom: 1rem;
        }

        .toolbar button {
            font: inherit;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.45rem 0.85rem;
            border: 1px solid var(--brand);
            border-radius: 0.375rem;
            background: var(--brand);
            cursor: pointer;
            color: #fff;
        }

        .toolbar button:hover {
            background: var(--brand-dark);
        }

        .content h1,
        .content h2 {
            color: var(--brand-ink);
            letter-spacing: -0.02em;
        }

        .content h1 {
            font-size: 1.1rem;
            margin: 0 0 0.5rem;
            font-weight: 700;
        }

        .content h2 {
            font-size: 0.95rem;
            margin: 1.25rem 0 0.5rem;
            font-weight: 700;
        }

        .meta {
            color: var(--muted);
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        th,
        td {
            border: none;
            border-bottom: 1px solid var(--line);
            padding: 0.38rem 0.45rem;
            text-align: start;
            vertical-align: top;
            color: #111827;
        }

        th {
            background: linear-gradient(90deg, var(--brand-dark), var(--brand));
            color: #fff;
            font-weight: 700;
            font-size: 0.75rem;
            border-bottom: 2px solid var(--brand-ink);
        }

        tbody tr:nth-child(even) td {
            background: var(--row-alt);
        }

        .sheet-footer {
            margin-top: 1.5rem;
            padding: 0.45rem 0.55rem;
            border-top: 2px solid var(--brand-soft);
            background: var(--brand-pale);
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--brand-ink);
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
                outline-offset: 1px;
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
