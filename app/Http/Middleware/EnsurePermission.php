<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Gate an admin section behind a permission key, e.g. ->middleware('perm:products').
     * Assumes the EnsureAdmin middleware has already verified panel access.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->hasPermission($permission)) {
            abort(403, 'Your role does not have access to this section.');
        }

        return $next($request);
    }
}
