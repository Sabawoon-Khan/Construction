<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <style>
        :root {
            color-scheme: light;
        }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            margin: 1.5rem;
            color: #111;
            line-height: 1.45;
        }
        h1 {
            font-size: 1.25rem;
            margin: 0 0 0.75rem;
        }
        .meta {
            color: #555;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 0.4rem 0.55rem;
            text-align: start;
        }
        th {
            background: #f4f4f5;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <button class="no-print" type="button" onclick="window.print()">Print</button>
    <h1>@yield('title', config('app.name'))</h1>
    <div class="meta">@yield('meta')</div>
    @yield('content')
</body>
</html>
