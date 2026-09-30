<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The Entra ID account can no longer use the application. The exception
 * message is the user-facing explanation.
 */
class AzureAccessRevokedException extends RuntimeException
{
    private function __construct(string $message, public readonly bool $accountDisabled)
    {
        parent::__construct($message);
    }

    public static function disabled(): self
    {
        return new self('Your Microsoft account is no longer active. Please contact an administrator.', true);
    }

    public static function noAccess(): self
    {
        return new self('Your Microsoft account no longer has access to this application. Please contact an administrator.', false);
    }
}
