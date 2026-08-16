<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\LookupItem;
use App\Support\LookupTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LookupController extends Controller
{
    use AuthorizesMisPermissions;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'settings.manage');

        $groups = LookupTable::groups();
        $active = $request->string('group')->toString() ?: array_key_first($groups);
        $search = $request->string('search')->toString();

        abort_unless($active && LookupTable::isAllowed($active), 404);

        $items = [];

        foreach (array_keys($groups) as $group) {
            $rows = LookupTable::forGroup($group);

            if ($search !== '' && $group === $active) {
                $needle = mb_strtolower($search);
                $rows = $rows
                    ->filter(fn ($row) => str_contains(mb_strtolower((string) $row->name), $needle))
                    ->values();
            }

            $items[$group] = $rows;
        }

        return Inertia::render('settings/Lookups/Index', [
            'groups' => collect($groups)->map(fn (array $meta, string $key) => [
                'key' => $key,
                'label' => $meta['label'],
            ])->values()->all(),
            'activeGroup' => $active,
            'items' => $items,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request, string $group): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.manage');
        abort_unless(LookupTable::isAllowed($group), 404);

        $validated = $request->validate(LookupTable::storeRules($group));
        LookupTable::create($group, $validated);

        return back()->with('success', __('Lookup item created.'));
    }

    public function update(Request $request, string $group, LookupItem $lookup): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.manage');
        abort_unless(LookupTable::isAllowed($group) && $lookup->group === $group, 404);

        $validated = $request->validate(LookupTable::updateRules($group, $lookup));
        $lookup->update($validated);

        return back()->with('success', __('Lookup item updated.'));
    }

    public function destroy(Request $request, string $group, LookupItem $lookup): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.manage');
        abort_unless(LookupTable::isAllowed($group) && $lookup->group === $group, 404);

        $lookup->delete();

        return back()->with('success', __('Lookup item deleted.'));
    }
}
