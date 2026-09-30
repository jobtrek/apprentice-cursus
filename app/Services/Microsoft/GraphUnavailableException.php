<?php

namespace App\Services\Microsoft;

use RuntimeException;

/**
 * Microsoft Graph could not be reached or answered with an error. Callers fail open:
 * the stored role is kept and nobody is logged out (see role_permissions.md).
 */
class GraphUnavailableException extends RuntimeException {}
