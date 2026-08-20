<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\ContactMessage;
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

        $totals = [
            'count' => ContactMessage::query()->count(),
            'unread' => ContactMessage::query()->unread()->count(),
        ];

        return Inertia::render('ContactMessages/Index', [
            'items' => $query->paginate(20)->withQueryString(),
            'filters' => [
                'search' => $search ?: null,
                'status' => in_array($status, ['unread', 'read'], true) ? $status : null,
            ],
            'totals' => $totals,
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
}
