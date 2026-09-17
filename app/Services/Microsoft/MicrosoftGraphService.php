<?php

namespace App\Services\Microsoft;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * App-only (client credentials) access to Microsoft Graph, used to re-validate
 * that a user's Entra ID account is still enabled and present in the tenant.
 */
class MicrosoftGraphService
{
    private const TOKEN_CACHE_KEY = 'microsoft-graph.app-token';

    /**
     * Determine whether the given Azure/Entra account is still enabled in the tenant.
     *
     * Returns null when the check could not be performed (e.g. Graph/token
     * endpoint unreachable) so callers can fail open rather than mass-logout
     * every SSO user during a Microsoft outage.
     */
    public function isAccountEnabled(string $azureId): ?bool
    {
        $token = $this->getAppToken();

        if (! $token) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->get("https://graph.microsoft.com/v1.0/users/{$azureId}", [
                    '$select' => 'accountEnabled',
                ]);
        } catch (ConnectionException $e) {
            Log::error('Microsoft Graph user lookup connection failed.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if ($response->status() === 404) {
            return false;
        }

        if (! $response->successful()) {
            Log::error('Microsoft Graph user lookup failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return (bool) $response->json('accountEnabled', true);
    }

    private function getAppToken(): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, 300, function (): ?string {
            $tenant = config('services.azure.tenant');

            try {
                $response = Http::asForm()->post("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
                    'client_id' => config('services.azure.client_id'),
                    'client_secret' => config('services.azure.client_secret'),
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ]);
            } catch (ConnectionException $e) {
                Log::error('Failed to reach Microsoft token endpoint.', [
                    'message' => $e->getMessage(),
                ]);

                return null;
            }

            if (! $response->successful()) {
                Log::error('Failed to acquire Microsoft Graph app-only token.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json('access_token');
        });
    }
}
