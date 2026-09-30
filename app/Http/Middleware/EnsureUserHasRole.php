<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to the given roles, e.g. `role:coach,trainer`.
 * The frontend only hides navigation; this is the actual access check.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role;

        abort_unless(
            $role instanceof UserRole && in_array($role->value, $roles, true),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
