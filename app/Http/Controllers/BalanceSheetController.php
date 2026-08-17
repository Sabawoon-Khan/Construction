<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Concerns\RendersPrintView;
use App\Models\Project;
use App\Services\BalanceSheetService;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BalanceSheetController extends Controller
{
    use ExportsSpreadsheet;
    use RendersPrintView;

    public function index(Request $request): InertiaResponse
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        return Inertia::render('BalanceSheet/Index', $this->payload($request));
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        $data = $this->payload($request);
        $rows = [];

        // Header
        $rows[] = ['Balance Sheet', '', '', ''];
        $rows[] = ['As of', $data['filters']['as_of'], '', ''];
        $rows[] = ['', '', '', ''];

        // Assets
        $rows[] = ['ASSETS', '', '', ''];
        $rows[] = ['Cash & Bank', '', '', $data['balance_sheet']['assets']['cash_and_bank']['total']];

        foreach ($data['balance_sheet']['assets']['cash_and_bank']['accounts'] as $account) {
            $rows[] = ['  '.$account['name'], $account['bank_name'] ?? '', $account['is_cash'] ? 'Cash' : 'Bank', $account['balance']];
        }

        $rows[] = ['  Cash on hand', '', '', $data['balance_sheet']['assets']['cash_and_bank']['cash_on_hand']];

        $rows[] = ['Accounts Receivable', '', '', $data['balance_sheet']['assets']['accounts_receivable']['total']];
        foreach ($data['balance_sheet']['assets']['accounts_receivable']['items'] as $item) {
            $rows[] = ['  '.$item['name'], '', '', $item['amount']];
        }

        $rows[] = ['Total Current Assets', '', '', $data['balance_sheet']['assets']['total_current']];
        $rows[] = ['TOTAL ASSETS', '', '', $data['balance_sheet']['assets']['total']];
        $rows[] = ['', '', '', ''];

        // Liabilities
        $rows[] = ['LIABILITIES', '', '', ''];
        $rows[] = ['Accounts Payable', '', '', $data['balance_sheet']['liabilities']['accounts_payable']['total']];
        foreach ($data['balance_sheet']['liabilities']['accounts_payable']['items'] as $item) {
            $rows[] = ['  '.$item['name'], '', '', $item['amount']];
        }

        $rows[] = ['Distributions Payable', '', '', $data['balance_sheet']['liabilities']['distributions_payable']['total']];
        foreach ($data['balance_sheet']['liabilities']['distributions_payable']['items'] as $item) {
            $rows[] = ['  '.$item['partner_name'], $item['project_name'] ?? '', '', $item['amount']];
        }

        $rows[] = ['Total Current Liabilities', '', '', $data['balance_sheet']['liabilities']['total_current']];
        $rows[] = ['TOTAL LIABILITIES', '', '', $data['balance_sheet']['liabilities']['total']];
        $rows[] = ['', '', '', ''];

        // Equity
        $rows[] = ['EQUITY', '', '', ''];
        $rows[] = ['Partner Capital', '', '', $data['balance_sheet']['equity']['partner_capital']['total']];
        foreach ($data['balance_sheet']['equity']['partner_capital']['items'] as $item) {
            $rows[] = ['  '.$item['partner_name'], '', '', $item['amount']];
        }

        $rows[] = ['Retained Earnings', '', '', $data['balance_sheet']['equity']['retained_earnings']];
        $rows[] = ['TOTAL EQUITY', '', '', $data['balance_sheet']['equity']['total']];
        $rows[] = ['', '', '', ''];

        $rows[] = ['TOTAL LIABILITIES + EQUITY', '', '', round($data['balance_sheet']['liabilities']['total'] + $data['balance_sheet']['equity']['total'], 2)];
        $rows[] = ['Balance Check', '', '', $data['balance_sheet']['metrics']['balance_check']];

        // Memo: inventory
        $rows[] = ['', '', '', ''];
        $rows[] = ['MEMO: Inventory (expensed at purchase)', '', '', $data['balance_sheet']['memo']['inventory']['total']];
        foreach ($data['balance_sheet']['memo']['inventory']['items'] as $item) {
            $rows[] = ['  '.$item['material_name'], $item['project_name'] ?? '', $item['on_hand'], $item['value']];
        }

        return $this->streamExport(
            'balance-sheet-'.$data['filters']['as_of'],
            ['Account / Party', 'Detail', 'Qty', 'Amount'],
            $rows,
            $request->string('format')->toString() ?: 'xlsx',
        );
    }

    public function print(Request $request): Response
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        return $this->printView('print.balance-sheet', $this->payload($request));
    }

    /** @return array<string, mixed> */
    private function payload(Request $request): array
    {
        $user = $request->user();
        $restricted = $user?->isRestrictedShareholder() ?? false;
        $projectId = $request->integer('project_id') ?: null;
        $reportCurrency = strtoupper($request->input('currency') ?? CurrencyService::baseCurrency());

        if ($restricted) {
            $allowed = $user->assignedProjectIds();

            if ($projectId && ! in_array($projectId, $allowed, true)) {
                abort(403);
            }

            if (! $projectId && count($allowed) === 1) {
                $projectId = $allowed[0];
            }
        }

        $asOf = $request->input('as_of')
            ? Carbon::parse($request->input('as_of'))->endOfDay()
            : now()->endOfDay();

        $projects = Project::query()
            ->orderBy('name')
            ->when($restricted, fn ($q) => $q->whereIn('id', $user->assignedProjectIds() ?: [0]))
            ->get(['id', 'name']);

        $balanceSheet = BalanceSheetService::asOf($asOf, $projectId);
        $convertedBalanceSheet = $reportCurrency === CurrencyService::baseCurrency()
            ? $balanceSheet
            : $this->convertBalanceSheet($balanceSheet, $reportCurrency, $asOf);

        return [
            'filters' => [
                'as_of' => $asOf->toDateString(),
                'project_id' => $projectId,
                'currency' => $reportCurrency,
            ],
            'restricted' => $restricted,
            'projects' => $projects,
            'report_currency' => $reportCurrency,
            'balance_sheet' => $convertedBalanceSheet,
        ];
    }

    /**
     * @param  array<string, mixed>  $balanceSheet
     * @return array<string, mixed>
     */
    private function convertBalanceSheet(array $balanceSheet, string $reportCurrency, Carbon $asOf): array
    {
        $baseCurrency = CurrencyService::baseCurrency();
        if ($baseCurrency === $reportCurrency) {
            return $balanceSheet;
        }

        $rate = CurrencyService::convert(1, $baseCurrency, $reportCurrency, $asOf);
        if ($rate === null) {
            return $balanceSheet;
        }

        return $this->convertNumericValues($balanceSheet, $rate);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function convertNumericValues(array $data, float $rate): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->convertNumericValues($value, $rate);
            } elseif (is_numeric($value) && (str_contains((string) $key, '_total') || in_array($key, ['amount', 'balance', 'value', 'total', 'paid', 'remaining', 'entitled_share', 'paid_total', 'retained_earnings', 'working_capital'], true))) {
                $data[$key] = round((float) $value * $rate, 2);
            }
        }

        return $data;
    }
}
