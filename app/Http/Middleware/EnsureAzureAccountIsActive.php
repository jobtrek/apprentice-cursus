<?php

namespace App\Http\Middleware;

use App\Services\MappingRolesService;
use App\Services\Microsoft\MicrosoftGraphService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-validates SSO users against Entra ID at most once per check interval.
 * If the account was disabled or removed from the tenant since the last
 * login, the current session is terminated on its next request.
 */
class EnsureAzureAccountIsActive
{
    public function __construct(
        private readonly MicrosoftGraphService $graph,
        private readonly MappingRolesService $mappingRoles,
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
            return $this->endSession($request, 'Your Microsoft account is no longer active. Please contact an administrator.');
        }

        // Re-check the group-derived role; fail open if Graph is unreachable.
        try {
            $mapping = $this->mappingRoles->resolveRole($user->azure_id);
        } catch (RuntimeException $e) {
            Log::error('Microsoft group re-check failed.', ['exception' => $e]);

            return $next($request);
        }

        if ($mapping === null) {
            return $this->endSession($request, 'Your Microsoft account no longer has access to this application. Please contact an administrator.');
        }

        if ($user->role !== $mapping['role']) {
            $user->forceFill(['role' => $mapping['role']])->save();
        }

        Cache::put($cacheKey, true, $interval);

        return $next($request);
    }

    private function endSession(Request $request, string $message): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', $message);
    }
}
