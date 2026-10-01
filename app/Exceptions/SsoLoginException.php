<?php

namespace App\Exceptions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SsoLoginException extends RuntimeException
{
    /** @param array<string, mixed> $logContext */
    private function __construct(
        string $userMessage,
        private readonly ?string $logMessage,
        private readonly array $logContext = [],
        private readonly string $logLevel = 'warning',
        ?Throwable $previous = null,
    ) {
        parent::__construct($userMessage, 0, $previous);
    }

    public static function sessionExpired(): self
    {
        return new self('Your Microsoft sign-in session expired. Please try again.', null);
    }

    public static function providerFailed(Throwable $e): self
    {
        return new self(
            'Could not sign in with Microsoft. Please try again.',
            'Microsoft SSO callback failed.',
            ['exception' => $e],
            'error',
            $e,
        );
    }

    public static function unusableProviderUser(string $class): self
    {
        return new self(
            'Could not sign in with Microsoft. Please try again.',
            'Microsoft SSO did not return a usable azure id.',
            ['class' => $class],
            'error',
        );
    }

    public static function groupLookupFailed(Throwable $e): self
    {
        return new self(
            'Could not verify your Microsoft groups. Please try again later.',
            'Microsoft SSO role lookup failed.',
            ['exception' => $e],
            'error',
            $e,
        );
    }

    public static function noAccess(string $azureId): self
    {
        return new self(
            'Your Microsoft account has no access to this application. Please contact an administrator.',
            'Microsoft SSO login refused: account is not in exactly one role group.',
            ['azure_id' => $azureId],
        );
    }

    public static function notSynced(string $azureId): self
    {
        return new self(
            'Your account has not been synced yet. Please try again tomorrow or ask an administrator to run the account sync.',
            'Microsoft SSO login refused: account has not been synced yet.',
            ['azure_id' => $azureId],
        );
    }

    public static function apprenticeshipMissing(string $name): self
    {
        return new self(
            'Could not verify your apprenticeship. Please contact an administrator.',
            'Microsoft SSO login refused: apprenticeship is not seeded.',
            ['apprenticeship' => $name],
            'error',
        );
    }

    public function report(): void
    {
        if ($this->logMessage === null) {
            return;
        }

        Log::log($this->logLevel, $this->logMessage, $this->logContext);
    }

    public function render(Request $request): RedirectResponse
    {
        return redirect()->route('login')->with('error', $this->getMessage());
    }
}
