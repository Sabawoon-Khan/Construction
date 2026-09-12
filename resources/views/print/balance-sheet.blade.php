@extends('print.layout')

@php
    $money = function (?array $amounts, mixed $fallback = 0): string {
        $afn = round((float) ($amounts['AFN'] ?? 0), 2);
        $usd = round((float) ($amounts['USD'] ?? 0), 2);

        if ($amounts === null && abs((float) $fallback) >= 0.01) {
            return number_format((float) $fallback, 2);
        }

        return 'AFN '.number_format($afn, 2).' · USD '.number_format($usd, 2);
    };
@endphp

@section('title', __('Balance Sheet'))

@section('meta')
    {{ __('As of') }} {{ $balance_sheet['as_of'] }}
    @if (!empty($filters['project_id']))
        · {{ __('Project') }} #{{ $filters['project_id'] }}
    @endif
    · AFN · USD
@endsection

@section('content')
    <h2>{{ __('Assets') }}</h2>
    <table>
        <tbody>
            <tr>
                <th>{{ __('Cash & Bank') }}</th>
                <td class="numeric">{{ $money($balance_sheet['assets']['cash_and_bank']['total_by_currency'] ?? null, $balance_sheet['assets']['cash_and_bank']['total']) }}</td>
            </tr>
            @foreach ($balance_sheet['assets']['cash_and_bank']['accounts'] as $account)
                <tr>
                    <td class="indent">{{ $account['name'] }}</td>
                    <td class="numeric">{{ $money($account['balance_by_currency'] ?? null, $account['balance']) }}</td>
                </tr>
            @endforeach
            <tr>
                <td class="indent">{{ __('Cash on hand') }}</td>
                <td class="numeric">{{ $money($balance_sheet['assets']['cash_and_bank']['cash_on_hand_by_currency'] ?? null, $balance_sheet['assets']['cash_and_bank']['cash_on_hand']) }}</td>
            </tr>
            <tr>
                <th>{{ __('Accounts Receivable') }}</th>
                <td class="numeric">{{ $money($balance_sheet['assets']['accounts_receivable']['total_by_currency'] ?? null, $balance_sheet['assets']['accounts_receivable']['total']) }}</td>
            </tr>
            @foreach ($balance_sheet['assets']['accounts_receivable']['items'] as $item)
                <tr>
                    <td class="indent">{{ $item['name'] }}</td>
                    <td class="numeric">{{ $money($item['amount_by_currency'] ?? null, $item['amount']) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <th>{{ __('Total Assets') }}</th>
                <td class="numeric">{{ $money($balance_sheet['assets']['total_by_currency'] ?? null, $balance_sheet['assets']['total']) }}</td>
            </tr>
        </tbody>
    </table>

    <h2 style="margin-top:1.25rem">{{ __('Liabilities') }}</h2>
    <table>
        <tbody>
            <tr>
                <th>{{ __('Accounts Payable') }}</th>
                <td class="numeric">{{ $money($balance_sheet['liabilities']['accounts_payable']['total_by_currency'] ?? null, $balance_sheet['liabilities']['accounts_payable']['total']) }}</td>
            </tr>
            @foreach ($balance_sheet['liabilities']['accounts_payable']['items'] as $item)
                <tr>
                    <td class="indent">{{ $item['name'] }}</td>
                    <td class="numeric">{{ $money($item['amount_by_currency'] ?? null, $item['amount']) }}</td>
                </tr>
            @endforeach
            <tr>
                <th>{{ __('Distributions Payable') }}</th>
                <td class="numeric">{{ $money($balance_sheet['liabilities']['distributions_payable']['total_by_currency'] ?? null, $balance_sheet['liabilities']['distributions_payable']['total']) }}</td>
            </tr>
            @foreach ($balance_sheet['liabilities']['distributions_payable']['items'] as $item)
                <tr>
                    <td class="indent">{{ $item['partner_name'] }} @if ($item['project_name']) · {{ $item['project_name'] }} @endif</td>
                    <td class="numeric">{{ $money($item['amount_by_currency'] ?? null, $item['amount']) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <th>{{ __('Total Liabilities') }}</th>
                <td class="numeric">{{ $money($balance_sheet['liabilities']['total_by_currency'] ?? null, $balance_sheet['liabilities']['total']) }}</td>
            </tr>
        </tbody>
    </table>

    <h2 style="margin-top:1.25rem">{{ __('Equity') }}</h2>
    <table>
        <tbody>
            <tr>
                <th>{{ __('Partner Capital') }}</th>
                <td class="numeric">{{ $money($balance_sheet['equity']['partner_capital']['total_by_currency'] ?? null, $balance_sheet['equity']['partner_capital']['total']) }}</td>
            </tr>
            @foreach ($balance_sheet['equity']['partner_capital']['items'] as $item)
                <tr>
                    <td class="indent">{{ $item['partner_name'] }}</td>
                    <td class="numeric">{{ $money($item['amount_by_currency'] ?? null, $item['amount']) }}</td>
                </tr>
            @endforeach
            <tr>
                <th>{{ __('Retained Earnings') }}</th>
                <td class="numeric">{{ $money($balance_sheet['equity']['retained_earnings_by_currency'] ?? null, $balance_sheet['equity']['retained_earnings']) }}</td>
            </tr>
            <tr class="total">
                <th>{{ __('Total Equity') }}</th>
                <td class="numeric">{{ $money($balance_sheet['equity']['total_by_currency'] ?? null, $balance_sheet['equity']['total']) }}</td>
            </tr>
        </tbody>
    </table>

    <h2 style="margin-top:1.25rem">{{ __('Balance Check') }}</h2>
    <table>
        <tbody>
            <tr class="total">
                <th>{{ __('Assets − Liabilities − Equity') }}</th>
                <td class="numeric">{{ $money($balance_sheet['metrics']['balance_check_by_currency'] ?? null, $balance_sheet['metrics']['balance_check']) }}</td>
            </tr>
        </tbody>
    </table>

    <h2 style="margin-top:1.25rem">{{ __('Memo: Inventory') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('Material') }}</th>
                <th>{{ __('Project') }}</th>
                <th>{{ __('Quantity') }}</th>
                <th class="numeric">{{ __('Value') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($balance_sheet['memo']['inventory']['items'] as $item)
                <tr>
                    <td>{{ $item['material_name'] }}</td>
                    <td>{{ $item['project_name'] ?? '—' }}</td>
                    <td>{{ number_format($item['on_hand'], 3) }}</td>
                    <td class="numeric">{{ $money($item['value_by_currency'] ?? null, $item['value']) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">{{ __('No stock on hand.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <style>
        .indent { padding-left: 1.25rem; }
        .numeric { text-align: right; white-space: nowrap; }
        tr.total th, tr.total td { font-weight: 700; border-top: 2px solid #111; }
    </style>
@endsection
