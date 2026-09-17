<?php

namespace App\Http\Middleware;

use App\Services\Microsoft\MicrosoftGraphService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-validates SSO users against Entra ID at most once per check interval.
 * If the account was disabled or removed from the tenant since the last
 * login, the current session is terminated on its next request.
 */
class EnsureAzureAccountIsActive
{
    public function __construct(private readonly MicrosoftGraphService $graph) {}

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
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your Microsoft account is no longer active. Please contact an administrator.');
        }

        Cache::put($cacheKey, true, $interval);

        return $next($request);
    }
}
