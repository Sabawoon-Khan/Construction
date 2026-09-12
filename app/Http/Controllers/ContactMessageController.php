<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\ContactMessage;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'contact_messages';

    use AuthorizesResourcePermissions;

    public function index(Request $request): Response
    {
        $query = ContactMessage::query()->latest();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $status = $request->string('status')->toString();
        if ($status === 'unread') {
            $query->unread();
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        $count = ContactMessage::query()->count();
        $unread = ContactMessage::query()->unread()->count();
        $totals = [
            'count' => $count,
            'unread' => $unread,
            'read' => max(0, $count - $unread),
        ];

        return Inertia::render('ContactMessages/Index', [
            'items' => $query->paginate(20)->withQueryString(),
            'filters' => [
                'search' => $search ?: null,
                'status' => in_array($status, ['unread', 'read'], true) ? $status : null,
            ],
            'totals' => $totals,
            'volumeChart' => $this->messagesByMonth(),
        ]);
    }

    public function show(ContactMessage $contactMessage): Response
    {
        $contactMessage->markRead();

        return Inertia::render('ContactMessages/Show', [
            'item' => $contactMessage->fresh(),
        ]);
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Contact message deleted.'),
        ]);

        return redirect()->route('contact-messages.index');
    }

    /**
     * @return list<array{key: string, label: string, value: int}>
     */
    private function messagesByMonth(): array
    {
        $to = Carbon::now()->endOfMonth();
        $from = Carbon::now()->subMonths(5)->startOfMonth();

        $buckets = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m');
            $buckets[$key] = [
                'key' => $key,
                'label' => $cursor->format('M'),
                'value' => 0,
            ];
            $cursor = $cursor->addMonth();
        }

        $rows = ContactMessage::query()
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->get(['created_at']);

        foreach ($rows as $row) {
            if (! $row->created_at) {
                continue;
            }
            $key = $row->created_at->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]['value']++;
            }
        }

        return array_values($buckets);
    }
}
