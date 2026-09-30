<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Apprenticeship;
use App\Models\User;
use App\Services\MappingRolesService;
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
    public function __construct(private readonly MappingRolesService $mappingRoles) {}

    public function redirectToProvider(): RedirectResponse
    {
        $driver = Socialite::driver('azure');

        if (! $driver instanceof AbstractProvider) {
            throw new RuntimeException('The azure Socialite driver must be an OAuth2 provider.');
        }

        return $driver->with(['prompt' => 'login'])->redirect();
    }

    public function callback(): RedirectResponse
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
        try {
            $mapping = $this->mappingRoles->resolveRole($azureUser->getId());
        } catch (RuntimeException $e) {
            Log::error('Microsoft SSO role lookup failed.', ['exception' => $e]);

            return $this->loginError('Could not verify your Microsoft groups. Please try again later.');
        }

        if ($mapping === null) {
            Log::warning('Microsoft SSO login refused: account is not in exactly one role group.', [
                'azure_id' => $azureUser->getId(),
            ]);

            return $this->loginError('Your Microsoft account has no access to this application. Please contact an administrator.');
        }

        $tenantId = config('services.azure.tenant');

        $user = User::where('azure_id', $azureUser->getId())->first();

        // Match on azure_id only: an email is not proof of identity, so a pre-existing
        // account without this azure_id is never adopted.
        if (! $user && User::where('email', $azureUser->getEmail())->exists()) {
            Log::warning('Microsoft SSO login refused: email already belongs to another account.', [
                'azure_id' => $azureUser->getId(),
            ]);

            return $this->loginError('Could not sign in with Microsoft. Please contact an administrator.');
        }

        $isNew = ! $user;

        if (! $user) {
            $user = new User([
                'name' => $azureUser->getName() ?: $azureUser->getNickname() ?: $azureUser->getEmail(),
                'email' => $azureUser->getEmail(),
                'azure_id' => $azureUser->getId(),
                'tenant_id' => $tenantId,
            ]);

            // The DB default is not loaded on an unsaved model; without this the check below sees null.
            $user->is_active = true;
        }

        $apprenticeshipId = null;

        if ($mapping['apprenticeship'] !== null) {
            $apprenticeshipId = Apprenticeship::where('name', $mapping['apprenticeship'])->value('id');

            if ($apprenticeshipId === null) {
                Log::error('Microsoft SSO login refused: apprenticeship is not seeded.', [
                    'apprenticeship' => $mapping['apprenticeship'],
                ]);

                return $this->loginError('Could not verify your apprenticeship. Please contact an administrator.');
            }
        }

        // A section change needs the apprentice's confirmation before grades move (user story):
        // keep the current apprenticeship until that flow exists.
        if ($user->apprenticeship_id !== null && $apprenticeshipId !== null && $user->apprenticeship_id !== $apprenticeshipId) {
            Log::warning('Microsoft SSO: section change pending confirmation, apprenticeship kept.', [
                'user_id' => $user->id,
                'from' => $user->apprenticeship_id,
                'to' => $apprenticeshipId,
            ]);
            $apprenticeshipId = $user->apprenticeship_id;
        }

        // Role and track are not mass-assignable (see User): Entra groups are their only source.
        $user->forceFill([
            'role' => $mapping['role'],
            'apprenticeship_id' => $apprenticeshipId,
        ])->save();

        if ($isNew) {
            Log::info('Microsoft SSO auto-provisioned a new account.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role->value,
            ]);
        }

        if (! $user->is_active) {
            Log::warning('Microsoft SSO login attempted for a deactivated account.', [
                'user_id' => $user->id,
            ]);

            return $this->loginError('Your account has been deactivated. Please contact an administrator.');
        }

        Auth::login($user);

        return redirect()->route('home');
    }

    private function loginError(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('error', $message);
    }
}
