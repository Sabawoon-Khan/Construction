<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $from = $request->filled('from')
            ? Carbon::parse($request->string('from'))->startOfDay()
            : now()->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::parse($request->string('to'))->endOfDay()
            : now()->endOfDay();

        $service = DashboardService::forUser($user);

        if ($user->partner) {
            return Inertia::render('Dashboard', [
                'mode' => 'shareholder',
                'stats' => $service->shareholderView($user->partner, $from, $to),
            ]);
        }

        return Inertia::render('Dashboard', [
            'mode' => 'admin',
            'stats' => $service->summary($from, $to),
        ]);
    }
}
