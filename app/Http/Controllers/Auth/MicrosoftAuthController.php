<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as AzureUser;

class MicrosoftAuthController extends Controller
{
    public function redirectToProvider()
    {
        return Socialite::driver('azure')->redirect();
    }

    public function callback()
    {
        $azureUser = Socialite::driver('azure')->user();

        if (! $azureUser instanceof AzureUser) {
            abort(500, 'Unexpected Socialite user type.');
        }

        $tenantId = $azureUser->user['tid'];

        if ($tenantId !== config('services.azure.tenant')) {
            abort(403, 'Unauthorized tenant.');
        }

        $user = User::where('azure_id', $azureUser->getId())
            ->orWhere('email', $azureUser->getEmail())
            ->first();

        if (! $user) {
            abort(403, 'No account found for this Microsoft user.');
        }

        $user->forceFill([
            'azure_id' => $azureUser->getId(),
            'tenant_id' => $tenantId,
        ])->save();

        Auth::login($user);

        return redirect()->route('home');
    }
}
