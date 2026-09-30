<?php

namespace App\Http\Middleware;

use App\Exceptions\ApprenticeshipNotSeededException;
use App\Exceptions\AzureAccessRevokedException;
use App\Services\AzureAccountSync;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class EnsureAzureAccountIsActive
{
    public function __construct(private readonly AzureAccountSync $sync) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->is_active) {
            return $this->endSession($request, 'Your account has been deactivated. Please contact an administrator.');
        }

        if (! $user->azure_id) {
            return $next($request);
        }

        $cacheKey = AzureAccountSync::checkCacheKey($user);

        if (Cache::has($cacheKey)) {
            return $next($request);
        }

        try {
            $group = $this->sync->check($user);
        } catch (AzureAccessRevokedException $e) {
            $this->sync->deactivate($user);

            return $this->endSession($request, $e->getMessage());
        } catch (RuntimeException $e) {
            Log::error('Microsoft group re-check failed.', ['exception' => $e]);
            Cache::put($cacheKey, true, AzureAccountSync::BACKOFF_SECONDS);

            return $next($request);
        }

        try {
            $this->sync->apply($user, $group);
        } catch (ApprenticeshipNotSeededException $e) {
            Log::error('Microsoft group re-check: apprenticeship is not seeded.', [
                'user_id' => $user->id,
                'apprenticeship' => $e->apprenticeship,
            ]);

            return $this->endSession($request, 'Could not verify your apprenticeship. Please contact an administrator.');
        }

        Cache::put($cacheKey, true, (int) config('services.azure.account_check_interval', 900));

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
