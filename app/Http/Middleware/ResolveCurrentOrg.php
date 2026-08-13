<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\CurrentOrg;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets CurrentOrg from the authenticated user's org_id when tenancy is enabled.
 * Users need an org_id column in the clone — kit ships the resolver only.
 */
class ResolveCurrentOrg
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('kit.features.tenancy')) {
            return $next($request);
        }

        $user = $request->user();
        $orgId = $user && isset($user->org_id) ? (int) $user->org_id : null;

        CurrentOrg::set($orgId ?: null);

        try {
            return $next($request);
        } finally {
            CurrentOrg::reset();
        }
    }
}
