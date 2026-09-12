<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Support\MisPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardV2Controller extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        [$from, $to, $preset] = MisPeriod::resolve(
            $request->string('preset')->toString() ?: null,
            $request->input('from'),
            $request->input('to'),
        );

        $service = DashboardService::forUser($user);

        if ($user->isRestrictedShareholder() && $user->partner) {
            return Inertia::render('DashboardV2', [
                'mode' => 'shareholder',
                'preset' => $preset,
                'stats' => $service->shareholderView($user->partner, $from, $to),
            ]);
        }

        return Inertia::render('DashboardV2', [
            'mode' => 'admin',
            'preset' => $preset,
            'stats' => $service->summary($from, $to),
        ]);
    }
}
