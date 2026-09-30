<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\SsoLoginException;
use App\Http\Controllers\Controller;
use App\Services\MicrosoftLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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

    public function callback(MicrosoftLoginService $login): RedirectResponse
    {
        try {
            $azureUser = Socialite::driver('azure')->user();
        } catch (InvalidStateException) {
            throw SsoLoginException::sessionExpired();
        } catch (Throwable $e) {
            throw SsoLoginException::providerFailed($e);
        }

        if (! $azureUser instanceof AzureUser || ! $azureUser->getId()) {
            throw SsoLoginException::unusableProviderUser($azureUser::class);
        }

        Auth::login($login->resolveUser($azureUser));

        return redirect()->route('home');
    }
}
