<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Concerns\RendersPrintView;
use App\Models\Project;
use App\Services\BalanceSheetService;
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

        $rows[] = ['Balance Sheet', '', '', '', ''];
        $rows[] = ['As of', $data['filters']['as_of'], '', '', ''];
        $rows[] = ['', '', '', '', ''];

        $rows[] = ['ASSETS', '', '', '', ''];
        $rows[] = ['Cash & Bank', '', '', '', $this->moneyCell($data['balance_sheet']['assets']['cash_and_bank']['total_by_currency'], $data['balance_sheet']['assets']['cash_and_bank']['total'])];

        foreach ($data['balance_sheet']['assets']['cash_and_bank']['accounts'] as $account) {
            $rows[] = [
                '  '.$account['name'],
                $account['bank_name'] ?? '',
                $account['is_cash'] ? 'Cash' : 'Bank',
                '',
                $this->moneyCell($account['balance_by_currency'] ?? null, $account['balance']),
            ];
        }

        $rows[] = ['  Cash on hand', '', '', '', $this->moneyCell($data['balance_sheet']['assets']['cash_and_bank']['cash_on_hand_by_currency'], $data['balance_sheet']['assets']['cash_and_bank']['cash_on_hand'])];

        $rows[] = ['Accounts Receivable', '', '', '', $this->moneyCell($data['balance_sheet']['assets']['accounts_receivable']['total_by_currency'], $data['balance_sheet']['assets']['accounts_receivable']['total'])];
        foreach ($data['balance_sheet']['assets']['accounts_receivable']['items'] as $item) {
            $rows[] = ['  '.$item['name'], '', '', '', $this->moneyCell($item['amount_by_currency'] ?? null, $item['amount'])];
        }

        $rows[] = ['Total Current Assets', '', '', '', $this->moneyCell($data['balance_sheet']['assets']['total_current_by_currency'], $data['balance_sheet']['assets']['total_current'])];
        $rows[] = ['TOTAL ASSETS', '', '', '', $this->moneyCell($data['balance_sheet']['assets']['total_by_currency'], $data['balance_sheet']['assets']['total'])];
        $rows[] = ['', '', '', '', ''];

        $rows[] = ['LIABILITIES', '', '', '', ''];
        $rows[] = ['Accounts Payable', '', '', '', $this->moneyCell($data['balance_sheet']['liabilities']['accounts_payable']['total_by_currency'], $data['balance_sheet']['liabilities']['accounts_payable']['total'])];
        foreach ($data['balance_sheet']['liabilities']['accounts_payable']['items'] as $item) {
            $rows[] = ['  '.$item['name'], '', '', '', $this->moneyCell($item['amount_by_currency'] ?? null, $item['amount'])];
        }

        $rows[] = ['Distributions Payable', '', '', '', $this->moneyCell($data['balance_sheet']['liabilities']['distributions_payable']['total_by_currency'], $data['balance_sheet']['liabilities']['distributions_payable']['total'])];
        foreach ($data['balance_sheet']['liabilities']['distributions_payable']['items'] as $item) {
            $rows[] = ['  '.$item['partner_name'], $item['project_name'] ?? '', '', '', $this->moneyCell($item['amount_by_currency'] ?? null, $item['amount'])];
        }

        $rows[] = ['Total Current Liabilities', '', '', '', $this->moneyCell($data['balance_sheet']['liabilities']['total_current_by_currency'], $data['balance_sheet']['liabilities']['total_current'])];
        $rows[] = ['TOTAL LIABILITIES', '', '', '', $this->moneyCell($data['balance_sheet']['liabilities']['total_by_currency'], $data['balance_sheet']['liabilities']['total'])];
        $rows[] = ['', '', '', '', ''];

        $rows[] = ['EQUITY', '', '', '', ''];
        $rows[] = ['Partner Capital', '', '', '', $this->moneyCell($data['balance_sheet']['equity']['partner_capital']['total_by_currency'], $data['balance_sheet']['equity']['partner_capital']['total'])];
        foreach ($data['balance_sheet']['equity']['partner_capital']['items'] as $item) {
            $rows[] = ['  '.$item['partner_name'], '', '', '', $this->moneyCell($item['amount_by_currency'] ?? null, $item['amount'])];
        }

        $rows[] = ['Retained Earnings', '', '', '', $this->moneyCell($data['balance_sheet']['equity']['retained_earnings_by_currency'], $data['balance_sheet']['equity']['retained_earnings'])];
        $rows[] = ['TOTAL EQUITY', '', '', '', $this->moneyCell($data['balance_sheet']['equity']['total_by_currency'], $data['balance_sheet']['equity']['total'])];
        $rows[] = ['', '', '', '', ''];

        $rows[] = [
            'TOTAL LIABILITIES + EQUITY',
            '',
            '',
            '',
            $this->moneyCell(
                [
                    'AFN' => round(($data['balance_sheet']['liabilities']['total_by_currency']['AFN'] ?? 0) + ($data['balance_sheet']['equity']['total_by_currency']['AFN'] ?? 0), 2),
                    'USD' => round(($data['balance_sheet']['liabilities']['total_by_currency']['USD'] ?? 0) + ($data['balance_sheet']['equity']['total_by_currency']['USD'] ?? 0), 2),
                ],
                round($data['balance_sheet']['liabilities']['total'] + $data['balance_sheet']['equity']['total'], 2),
            ),
        ];
        $rows[] = ['Balance Check', '', '', '', $this->moneyCell($data['balance_sheet']['metrics']['balance_check_by_currency'], $data['balance_sheet']['metrics']['balance_check'])];

        $rows[] = ['', '', '', '', ''];
        $rows[] = ['MEMO: Inventory (expensed at purchase)', '', '', '', $this->moneyCell($data['balance_sheet']['memo']['inventory']['total_by_currency'], $data['balance_sheet']['memo']['inventory']['total'])];
        foreach ($data['balance_sheet']['memo']['inventory']['items'] as $item) {
            $rows[] = ['  '.$item['material_name'], $item['project_name'] ?? '', $item['on_hand'], '', $this->moneyCell($item['value_by_currency'] ?? null, $item['value'])];
        }

        return $this->streamExport(
            'balance-sheet-'.$data['filters']['as_of'],
            ['Account / Party', 'Detail', 'Qty', '', 'Amount (AFN · USD)'],
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

        return [
            'filters' => [
                'as_of' => $asOf->toDateString(),
                'project_id' => $projectId,
            ],
            'restricted' => $restricted,
            'projects' => $projects,
            'balance_sheet' => BalanceSheetService::asOf($asOf, $projectId),
        ];
    }

    /**
     * @param  array<string, float>|null  $amounts
     */
    private function moneyCell(?array $amounts, mixed $fallback = 0): string
    {
        $afn = round((float) ($amounts['AFN'] ?? 0), 2);
        $usd = round((float) ($amounts['USD'] ?? 0), 2);

        if ($amounts === null && abs((float) $fallback) >= 0.01) {
            return number_format((float) $fallback, 2);
        }

        return 'AFN '.number_format($afn, 2).' · USD '.number_format($usd, 2);
    }
}
