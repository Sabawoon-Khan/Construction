<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuthenticationLog;
use App\Support\EfficientQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticationLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('audit.view'), 403);

        $search = $request->string('search')->trim()->toString();
        $event = $request->string('event')->trim()->toString();
        $success = $request->string('success')->trim()->toString();
        $perPage = min(100, max(10, $request->integer('per_page', 25)));

        $logs = AuthenticationLog::query()
            ->with('user:id,name,email')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    EfficientQuery::applySearch(
                        $builder,
                        $search,
                        prefixColumns: ['email', 'ip_address', 'city', 'country', 'country_code', 'isp', 'browser'],
                        containsColumns: ['location_label', 'region'],
                        exactColumns: ['email', 'ip_address', 'country_code'],
                    );
                    EfficientQuery::orWhereUserMatches($builder, $search);
                });
            })
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->when($success === '1', fn ($query) => $query->where('success', true))
            ->when($success === '0', fn ($query) => $query->where('success', false))
            ->orderByDesc('logged_at')
            ->orderByDesc('id')
            ->simplePaginate($perPage)
            ->withQueryString();

        return Inertia::render('settings/AuthenticationLogs/Index', [
            'logs' => $logs,
            'events' => [
                AuthenticationLog::EVENT_LOGIN_SUCCESS,
                AuthenticationLog::EVENT_LOGIN_FAILED,
                AuthenticationLog::EVENT_LOGOUT,
                AuthenticationLog::EVENT_TWO_FACTOR_CHALLENGED,
                AuthenticationLog::EVENT_TWO_FACTOR_FAILED,
                AuthenticationLog::EVENT_LOCKOUT,
                AuthenticationLog::EVENT_SESSION_REVOKED,
            ],
            'filters' => [
                'search' => $search ?: null,
                'event' => $event ?: null,
                'success' => in_array($success, ['0', '1'], true) ? $success : null,
            ],
        ]);
    }
}
