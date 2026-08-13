<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Sale;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatusController extends Controller
{
    /**
     * @var array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    private const RESOURCE_MAP = [
        'projects' => Project::class,
        'units' => Unit::class,
        'sales' => Sale::class,
        'payments' => Payment::class,
        'expenses' => Expense::class,
        'incomes' => Income::class,
    ];

    public function update(Request $request, string $resource, int $id): RedirectResponse
    {
        $modelClass = self::RESOURCE_MAP[$resource] ?? abort(404);

        abort_unless($request->user()?->can("{$resource}.edit"), 403);

        $validated = $request->validate([
            'status' => 'required|string|max:50',
        ]);

        $model = $modelClass::findOrFail($id);
        $model->update(['status' => $validated['status']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Status updated.']);

        return redirect()->back();
    }
}
