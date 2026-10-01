<?php

namespace App\Services\Microsoft;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * App-only (client credentials) access to Microsoft Graph, used to resolve a
 * user's group memberships and to re-validate that the Entra ID account is
 * still enabled and present in the tenant.
 */
class MicrosoftGraphService
{
    private const TOKEN_CACHE_KEY = 'microsoft-graph.app-token';

    /**
     * Transitive group memberships of the account, following `@odata.nextLink`
     * paging. Returns null when the lookup could not be performed.
     *
     * @return list<array{id?: string, displayName?: string}>|null
     */
    public function getGroups(string $azureId): ?array
    {
        $token = $this->getAppToken();

        if (! $token) {
            return null;
        }

        $url = "https://graph.microsoft.com/v1.0/users/{$azureId}/transitiveMemberOf";
        $query = ['$select' => 'id,displayName'];
        $groups = [];

        while ($url !== null) {
            try {
                $response = Http::withToken($token)->acceptJson()->get($url, $query);
            } catch (ConnectionException $e) {
                Log::error('Microsoft Graph group lookup failed.', ['message' => $e->getMessage()]);

                return null;
            }

            if (! $response->successful()) {
                Log::error('Microsoft Graph group lookup failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            array_push($groups, ...($response->json('value') ?? []));

            $url = $response->json()['@odata.nextLink'] ?? null;
            $query = [];
        }

        return $groups;
    }

    /**
     * Transitive members of a group that are users, following `@odata.nextLink`
     * paging. The OData cast and `$select` are advanced queries on directory
     * objects, hence `ConsistencyLevel: eventual` with `$count=true`. Returns
     * null when the lookup could not be performed.
     *
     * @return list<array{id?: string, displayName?: string, mail?: string|null, userPrincipalName?: string|null, accountEnabled?: bool|null}>|null
     */
    public function getGroupMembers(string $groupId): ?array
    {
        $token = $this->getAppToken();

        if (! $token) {
            return null;
        }

        $url = "https://graph.microsoft.com/v1.0/groups/{$groupId}/transitiveMembers/microsoft.graph.user";
        $query = [
            '$select' => 'id,displayName,mail,userPrincipalName,accountEnabled',
            '$count' => 'true',
            '$top' => 999,
        ];
        $members = [];

        while ($url !== null) {
            try {
                $response = Http::withToken($token)
                    ->withHeaders(['ConsistencyLevel' => 'eventual'])
                    ->acceptJson()
                    ->get($url, $query);
            } catch (ConnectionException $e) {
                Log::error('Microsoft Graph group members lookup failed.', [
                    'group_id' => $groupId,
                    'message' => $e->getMessage(),
                ]);

                return null;
            }

            if (! $response->successful()) {
                Log::error('Microsoft Graph group members lookup failed.', [
                    'group_id' => $groupId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            // A page without a `value` list is a failed lookup, not an empty group:
            // treating it as empty would deactivate every member of the group.
            $page = $response->json('value');

            if (! is_array($page) || ! array_is_list($page)) {
                Log::error('Microsoft Graph group members lookup returned no member list.', [
                    'group_id' => $groupId,
                    'body' => $response->body(),
                ]);

                return null;
            }

            array_push($members, ...$page);

            $url = $response->json()['@odata.nextLink'] ?? null;
            $query = [];
        }

        return $members;
    }

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
