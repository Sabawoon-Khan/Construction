@extends('print.layout')

@section('title', __('Balance Sheet'))

@section('meta')
    {{ __('As of') }} {{ $balance_sheet['as_of'] }}
    @if (!empty($filters['project_id']))
        · {{ __('Project') }} #{{ $filters['project_id'] }}
    @endif
@endsection

@section('content')
    <h2>{{ __('Assets') }}</h2>
    <table>
        <tbody>
            <tr>
                <th>{{ __('Cash & Bank') }}</th>
                <td class="numeric">{{ number_format($balance_sheet['assets']['cash_and_bank']['total'], 2) }}</td>
            </tr>
            @foreach ($balance_sheet['assets']['cash_and_bank']['accounts'] as $account)
                <tr>
                    <td class="indent">{{ $account['name'] }}</td>
                    <td class="numeric">{{ number_format($account['balance'], 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td class="indent">{{ __('Cash on hand') }}</td>
                <td class="numeric">{{ number_format($balance_sheet['assets']['cash_and_bank']['cash_on_hand'], 2) }}</td>
            </tr>
            <tr>
                <th>{{ __('Accounts Receivable') }}</th>
                <td class="numeric">{{ number_format($balance_sheet['assets']['accounts_receivable']['total'], 2) }}</td>
            </tr>
            @foreach ($balance_sheet['assets']['accounts_receivable']['items'] as $item)
                <tr>
                    <td class="indent">{{ $item['name'] }}</td>
                    <td class="numeric">{{ number_format($item['amount'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <th>{{ __('Total Assets') }}</th>
                <td class="numeric">{{ number_format($balance_sheet['assets']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2 style="margin-top:1.25rem">{{ __('Liabilities') }}</h2>
    <table>
        <tbody>
            <tr>
                <th>{{ __('Accounts Payable') }}</th>
                <td class="numeric">{{ number_format($balance_sheet['liabilities']['accounts_payable']['total'], 2) }}</td>
            </tr>
            @foreach ($balance_sheet['liabilities']['accounts_payable']['items'] as $item)
                <tr>
                    <td class="indent">{{ $item['name'] }}</td>
                    <td class="numeric">{{ number_format($item['amount'], 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <th>{{ __('Distributions Payable') }}</th>
                <td class="numeric">{{ number_format($balance_sheet['liabilities']['distributions_payable']['total'], 2) }}</td>
            </tr>
            @foreach ($balance_sheet['liabilities']['distributions_payable']['items'] as $item)
                <tr>
                    <td class="indent">{{ $item['partner_name'] }} @if ($item['project_name']) · {{ $item['project_name'] }} @endif</td>
                    <td class="numeric">{{ number_format($item['amount'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <th>{{ __('Total Liabilities') }}</th>
                <td class="numeric">{{ number_format($balance_sheet['liabilities']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2 style="margin-top:1.25rem">{{ __('Equity') }}</h2>
    <table>
        <tbody>
            <tr>
                <th>{{ __('Partner Capital') }}</th>
                <td class="numeric">{{ number_format($balance_sheet['equity']['partner_capital']['total'], 2) }}</td>
            </tr>
            @foreach ($balance_sheet['equity']['partner_capital']['items'] as $item)
                <tr>
                    <td class="indent">{{ $item['partner_name'] }}</td>
                    <td class="numeric">{{ number_format($item['amount'], 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <th>{{ __('Retained Earnings') }}</th>
                <td class="numeric">{{ number_format($balance_sheet['equity']['retained_earnings'], 2) }}</td>
            </tr>
            <tr class="total">
                <th>{{ __('Total Equity') }}</th>
                <td class="numeric">{{ number_format($balance_sheet['equity']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2 style="margin-top:1.25rem">{{ __('Balance Check') }}</h2>
    <table>
        <tbody>
            <tr class="total">
                <th>{{ __('Assets − Liabilities − Equity') }}</th>
                <td class="numeric">{{ number_format($balance_sheet['metrics']['balance_check'], 2) }}</td>
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
                    <td class="numeric">{{ number_format($item['value'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">{{ __('No stock on hand.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <style>
        .indent { padding-left: 1.25rem; }
        .numeric { text-align: right; }
        tr.total th, tr.total td { font-weight: 700; border-top: 2px solid #111; }
    </style>
@endsection
