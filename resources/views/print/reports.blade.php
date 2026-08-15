@extends('print.layout')

@section('title', __('MIS Report'))

@section('meta')
    {{ $filters['from'] }} — {{ $filters['to'] }}
    @if (!empty($filters['project_id']))
        · {{ __('Project') }} #{{ $filters['project_id'] }}
    @endif
@endsection

@section('content')
    <h2>{{ __('Summary') }}</h2>
    <table>
        <tbody>
            <tr><th>{{ __('Sales') }}</th><td>{{ number_format($summary['sales_total'] ?? 0, 2) }}</td></tr>
            <tr><th>{{ __('Other income') }}</th><td>{{ number_format($summary['income_total'] ?? 0, 2) }}</td></tr>
            <tr><th>{{ __('Revenue') }}</th><td>{{ number_format($summary['revenue_total'] ?? 0, 2) }}</td></tr>
            <tr><th>{{ __('Operating expenses') }}</th><td>{{ number_format($summary['operating_expense_total'] ?? 0, 2) }}</td></tr>
            <tr><th>{{ __('Paid salaries') }}</th><td>{{ number_format($summary['salary_total'] ?? 0, 2) }}</td></tr>
            <tr><th>{{ __('Net profit') }}</th><td>{{ number_format($summary['net_profit'] ?? 0, 2) }}</td></tr>
        </tbody>
    </table>

    <h2 style="margin-top:1.25rem">{{ __('Shareholder distribution') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('Shareholder') }}</th>
                <th>{{ __('Project') }}</th>
                <th>{{ __('Share %') }}</th>
                <th>{{ __('Entitled') }}</th>
                <th>{{ __('Paid') }}</th>
                <th>{{ __('Balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($partners as $partner)
                <tr>
                    <td>{{ $partner['name'] }}</td>
                    <td>{{ $partner['project_name'] ?? '—' }}</td>
                    <td>{{ number_format($partner['ownership_percentage'] ?? 0, 2) }}</td>
                    <td>{{ number_format($partner['entitled_share'] ?? 0, 2) }}</td>
                    <td>{{ number_format($partner['paid_total'] ?? 0, 2) }}</td>
                    <td>{{ number_format($partner['balance_owed'] ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">{{ __('No shareholders') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
