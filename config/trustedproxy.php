<?php

return [
    /*
     * Comma-separated IPs/CIDRs of the TLS reverse proxy, as the app container
     * sees them (Docker may show its bridge gateway instead of the proxy's own IP).
     * Read by Laravel's global TrustProxies middleware.
     */
    'proxies' => env('TRUSTED_PROXIES'),
];
