<?php

namespace App\Http\Controllers\Auth;

use App\Actions\SyncEntraRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Microsoft\GraphUnavailableException;
use App\Services\Microsoft\MappingRolesService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as AzureUser;
use RuntimeException;
use Throwable;

class MicrosoftAuthController extends Controller
{
    public function redirectToProvider(): RedirectResponse
    {
        $driver = Socialite::driver('azure');

        if (! $driver instanceof AbstractProvider) {
            throw new RuntimeException('The azure Socialite driver must be an OAuth2 provider.');
        }

        return $driver->with(['prompt' => 'login'])->redirect();
    }

    public function callback(MappingRolesService $roles, SyncEntraRole $sync): RedirectResponse
    {
        try {
            $azureUser = Socialite::driver('azure')->user();

            if (! $azureUser instanceof AzureUser || ! $azureUser->getId()) {
                Log::error('Microsoft SSO did not return a usable azure id.', [
                    'class' => $azureUser::class,
                ]);

                return $this->loginError('Could not sign in with Microsoft. Please try again.');
            }

        } catch (InvalidStateException) {
            return $this->loginError('Your Microsoft sign-in session expired. Please try again.');
        } catch (Throwable $e) {
            Log::error('Microsoft SSO callback failed.', ['exception' => $e]);

            return $this->loginError('Could not sign in with Microsoft. Please try again.');
        }
        $user = User::where('azure_id', $azureUser->getId())->first();

        try {
            $assignment = $roles->forUser($azureUser->getId());
        } catch (GraphUnavailableException) {
            // Fail open for known, active users; a new user cannot be let in without a role.
            if (! $user || ! $user->is_active) {
                return $this->loginError('Could not verify your access with Microsoft. Please try again later.');
            }

            Auth::login($user);

            return redirect()->route('home');
        }

        if (! $assignment) {
            if ($user) {
                $sync->revoke($user);
            }

            return $this->loginError('Your Microsoft account has no access to this application. Please contact your trainer.');
        }

        if (! $user) {
            $user = new User([
                'name' => $azureUser->getName() ?: $azureUser->getNickname() ?: $azureUser->getEmail(),
                'email' => $azureUser->getEmail(),
                'azure_id' => $azureUser->getId(),
                'tenant_id' => config('services.azure.tenant'),
            ]);
        }

        try {
            $sync->apply($user, $assignment);
        } catch (UniqueConstraintViolationException) {
            // Users are matched on azure_id only; an existing row with this email but
            // another azure_id is never linked automatically.
            Log::warning('Microsoft SSO email already belongs to another account.', [
                'azure_id' => $azureUser->getId(),
            ]);

            return $this->loginError('This email is already linked to another account. Please contact your trainer.');
        }

        Auth::login($user);

        return redirect()->route('home');
    }

    private function loginError(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('error', $message);
    }
}
