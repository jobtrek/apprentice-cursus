<?php

namespace App\Http\Middleware;

use App\Actions\SyncEntraRole;
use App\Services\Microsoft\GraphUnavailableException;
use App\Services\Microsoft\MappingRolesService;
use App\Services\Microsoft\MicrosoftGraphService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-validates SSO users against Entra ID at most once per check interval.
 * If the account was disabled or removed from the tenant since the last
 * login, the current session is terminated on its next request. The role and
 * section are re-synced on the same interval, and a user without a role is
 * deactivated and logged out.
 */
class EnsureAzureAccountIsActive
{
    public function __construct(
        private readonly MicrosoftGraphService $graph,
        private readonly MappingRolesService $roles,
        private readonly SyncEntraRole $sync,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->azure_id) {
            return $next($request);
        }

        $cacheKey = "azure-account-check:{$user->id}";
        $interval = (int) config('services.azure.account_check_interval', 900);

        if (Cache::has($cacheKey)) {
            return $next($request);
        }

        $enabled = $this->graph->isAccountEnabled($user->azure_id);

        if ($enabled === false) {
            return $this->logout($request, 'Your Microsoft account is no longer active. Please contact an administrator.');
        }

        try {
            $assignment = $this->roles->forUser($user->azure_id);

            if (! $assignment) {
                $this->sync->revoke($user);

                return $this->logout($request, 'Your Microsoft account no longer has access to this application.');
            }

            $this->sync->apply($user, $assignment);
        } catch (GraphUnavailableException) {
            // Fail open: keep the stored role until Graph answers again.
        }

        Cache::put($cacheKey, true, $interval);

        return $next($request);
    }

    private function logout(Request $request, string $message): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', $message);
    }
}
