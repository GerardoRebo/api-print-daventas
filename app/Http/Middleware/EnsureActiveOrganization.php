<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to set and validate user's active organization context.
 * After authentication, ensures the user has an active organization selected.
 * Stores active organization ID in session for the lifetime of that session.
 */
class EnsureActiveOrganization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Check if user has an active organization set in session
            if (!session()->has('active_organization_id')) {
                // Get user's first active organization
                $activeOrg = $user->activeOrganizations()->first();

                if (!$activeOrg) {
                    // User has no active organizations - they need to be invited/assigned to one
                    return response()->json([
                        'error' => 'No organizations assigned',
                        'message' => 'User has no active organization assignments',
                    ], 403);
                }

                // Set the active organization in session
                session(['active_organization_id' => $activeOrg->id]);
            } else {
                // Validate that the user still belongs to the active organization
                $activeOrgId = session('active_organization_id');
                if (!$user->belongsToOrganization($activeOrgId)) {
                    // User no longer has access to this organization - clear it
                    session()->forget('active_organization_id');

                    // Try to set a new default org
                    $activeOrg = $user->activeOrganizations()->first();
                    if ($activeOrg) {
                        session(['active_organization_id' => $activeOrg->id]);
                    } else {
                        return response()->json([
                            'error' => 'Organization access revoked',
                            'message' => 'You no longer have access to that organization',
                        ], 403);
                    }
                }
            }
        }

        return $next($request);
    }
}
