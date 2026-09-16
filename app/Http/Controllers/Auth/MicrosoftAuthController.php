<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftAuthController extends Controller
{
    public function redirectToProvider()
    {
        return Socialite::driver('azure')->redirect();
    }

    public function callback()
    {
        $user = Socialite::driver('azure')->user();

        if ($user->user->tenantId !== config('services.azure.tenant')) {
            abort(403, 'Unauthorized tenant.');
        }



        return redirect()->route('home');
    }
}
