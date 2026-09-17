<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as AzureUser;
use Laravel\Socialite\Two\AbstractProvider;
use Throwable;



/** @var AbstractProvider $driver */

class MicrosoftAuthController extends Controller
{
    public function redirectToProvider()
    {
        $driver = Socialite::driver('azure');

        return $driver->with(['prompt' => 'login'])->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $azureUser = Socialite::driver('azure')->user();

        } catch (InvalidStateException) {
            return $this->loginError('Your Microsoft sign-in session expired. Please try again.');
        } catch (Throwable $e) {
            Log::error('Microsoft SSO callback failed.', ['exception' => $e]);

            return $this->loginError('Could not sign in with Microsoft. Please try again.');
        }
        // this will invalidate and block anyone trying to connect with a non existing account.
        if (! $azureUser instanceof AzureUser || ! $azureUser->getId()) {
            Log::error('Microsoft SSO did not return a usable azure id.', [
                'class' => $azureUser::class,
            ]);

            return $this->loginError('Could not sign in with Microsoft. Please try again.');
        }

        $tenantId = config('services.azure.tenant');

        $user = User::where('azure_id', $azureUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $azureUser->getEmail())->first();

            if ($user) {
                $user->forceFill([
                    'azure_id' => $azureUser->getId(),
                    'tenant_id' => $tenantId,
                ])->save();
            }
        }

        if (! $user) {
            $user = User::create([
                'azure_id' => $azureUser->getId(),
                'tenant_id' => $tenantId,
                'name' => $azureUser->getName(),
                'email' => $azureUser->getEmail(),
            ]);
        }

        Auth::login($user);

        return redirect()->route('home');
    }

    private function loginError(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('error', $message);
    }
}
