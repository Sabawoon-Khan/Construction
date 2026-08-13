<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\InventoryTransaction;
use App\Models\Material;
use App\Models\Project;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'purchases';

    use AuthorizesResourcePermissions;

    /** @return list<Middleware> */
    public static function middleware(): array
    {
        $module = static::$permissionModule;

        return [
            new Middleware('permission:'.$module.'.view', only: ['index', 'show']),
            new Middleware('permission:'.$module.'.create', only: ['create', 'store', 'createIssue', 'storeIssue']),
        ];
    }

    public function index(Request $request): Response
    {
        $type = $request->string('type')->toString();
        if (! in_array($type, ['purchase', 'issue', ''], true)) {
            $type = '';
        }

        $query = InventoryTransaction::query()
            ->with([
                'material:id,code,name,material_unit_id',
                'material.materialUnit:id,unit_name',
                'supplier:id,name',
                'project:id,name',
                'expense:id,expense_no',
            ])
            ->latest('transaction_date');

        if ($type !== '') {
            $query->where('type', $type);
        }

        if ($request->filled('material_id')) {
            $query->where('material_id', $request->integer('material_id'));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        return Inertia::render('Purchases/Index', [
            'items' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['type', 'material_id', 'project_id']),
            'materials' => Material::query()->where('is_active', true)->with('materialUnit:id,unit_name')->orderBy('name')->get(['id', 'code', 'name', 'material_unit_id']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        $materialId = $request->integer('material_id') ?: null;

        return Inertia::render('Purchases/Create', [
            ...$this->formOptions(),
            'preselectedMaterialId' => $materialId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'project_id' => 'nullable|exists:projects,id',
            'quantity' => 'required|numeric|min:0.001',
            'unit_cost' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'transaction_date' => 'required|date',
            'payment_method' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $material = Material::query()->findOrFail($data['material_id']);
        $quantity = (float) $data['quantity'];
        $unitCost = (float) $data['unit_cost'];
        $totalCost = round($quantity * $unitCost, 2);
        $supplier = isset($data['supplier_id'])
            ? Supplier::query()->find($data['supplier_id'])
            : null;

        DB::transaction(function () use ($data, $material, $quantity, $unitCost, $totalCost, $supplier) {
            $expense = Expense::create([
                'project_id' => $data['project_id'] ?? null,
                'expense_category_id' => $this->materialsCategoryId(),
                'supplier_id' => $data['supplier_id'] ?? null,
                'expense_no' => 'EXP-'.Str::upper(Str::random(8)),
                'expense_date' => $data['transaction_date'],
                'paid_to' => $supplier?->name,
                'amount' => $totalCost,
                'currency' => $data['currency'],
                'payment_method' => $data['payment_method'],
                'reference_no' => null,
                'status' => 'approved',
                'description' => sprintf(
                    'Material purchase: %s (%s %s)',
                    $material->name,
                    rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.'),
                    $material->unit
                ),
            ]);

            InventoryTransaction::create([
                'project_id' => $data['project_id'] ?? null,
                'material_id' => $material->id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'expense_id' => $expense->id,
                'type' => 'purchase',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'currency' => $data['currency'],
                'transaction_date' => $data['transaction_date'],
                'reference_no' => null,
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['project_id'])) {
                InventoryTransaction::create([
                    'project_id' => $data['project_id'],
                    'material_id' => $material->id,
                    'type' => 'issue',
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'currency' => $data['currency'],
                    'transaction_date' => $data['transaction_date'],
                    'notes' => 'Auto-issued from direct-to-project purchase.',
                ]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Purchase recorded.']);

        return redirect()->route('purchases.index', ['type' => 'purchase']);
    }

    public function show(InventoryTransaction $purchase): Response
    {
        $purchase->load([
            'material:id,code,name,material_unit_id',
            'material.materialUnit:id,unit_name',
            'supplier:id,name',
            'project:id,name',
            'expense:id,expense_no,expense_date,payment_method,reference_no,status',
        ]);

        return Inertia::render('Purchases/Show', [
            'item' => $purchase,
        ]);
    }

    public function createIssue(Request $request): Response
    {
        $materialId = $request->integer('material_id') ?: null;

        return Inertia::render('Purchases/IssueCreate', [
            ...$this->formOptions(),
            'preselectedMaterialId' => $materialId,
        ]);
    }

    public function storeIssue(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'project_id' => 'required|exists:projects,id',
            'quantity' => 'required|numeric|min:0.001',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $material = Material::query()->findOrFail($data['material_id']);
        $quantity = (float) $data['quantity'];
        $available = $material->currentStock();

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => sprintf(
                    'Not enough stock. Available: %s %s.',
                    rtrim(rtrim(number_format($available, 3, '.', ''), '0'), '.'),
                    $material->unit
                ),
            ]);
        }

        $unitCost = $material->averageUnitCost();
        $totalCost = round($quantity * $unitCost, 2);

        InventoryTransaction::create([
            'project_id' => $data['project_id'],
            'material_id' => $material->id,
            'type' => 'issue',
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'currency' => $material->currency ?: 'USD',
            'transaction_date' => $data['transaction_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Material issued to project.']);

        return redirect()->route('purchases.index', ['type' => 'issue']);
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'materials' => Material::query()
                ->where('is_active', true)
                ->with('materialUnit:id,unit_name')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'material_unit_id'])
                ->map(fn (Material $material) => [
                    'id' => $material->id,
                    'code' => $material->code,
                    'name' => $material->name,
                    'unit' => $material->unit,
                    'current_stock' => $material->currentStock(),
                    'average_unit_cost' => $material->averageUnitCost(),
                    'stock_value' => round($material->currentStock() * $material->averageUnitCost(), 2),
                ]),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function materialsCategoryId(): int
    {
        $category = ExpenseCategory::query()
            ->where(function ($query) {
                $query->where('code', 'materials')
                    ->orWhere('name', 'Materials');
            })
            ->first();

        if ($category) {
            return $category->id;
        }

        return ExpenseCategory::query()->create([
            'name' => 'Materials',
            'code' => 'materials',
            'description' => 'Auto-created for material purchases.',
            'is_active' => true,
        ])->id;
    }
}
