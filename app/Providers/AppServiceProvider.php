<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use SocialiteProviders\Azure\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Each scan costs Gemini + Jev calls, so cap it per user. A 422 on `file`
        // shows up in the dialog like any other upload error.
        RateLimiter::for('ocr', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();
            $tooMany = fn () => response()->json(['errors' => [
                'file' => ['Limite de 10 analyses automatiques par jour atteinte, réessayez demain ou saisissez la note à la main.'],
            ]], 422);

            return Limit::perDay(10)->by($key)->response($tooMany);
        });

        // Spatie's documented super-admin pattern. The environment is checked at
        // call time inside isLocalAdmin(), so outside local the callback always
        // returns null and the admin role grants nothing. Registered unconditionally
        // so tests can switch the environment after boot. Typed as User so the Gate
        // skips it for guests.
        Gate::before(fn (User $user): ?bool => $user->isLocalAdmin() ? true : null);

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite(
                'azure',
                Provider::class
            );
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        // Short aliases keep model_type (Spatie roles) stable if a model is ever renamed or moved.
        // enforceMorphMap() also throws on any model used polymorphically that is missing
        // here — so anything adding a morph relation later (database notifications via
        // Notifiable, an activity log, a media library) must be added to this map.
        Relation::enforceMorphMap([
            'user' => User::class,
        ]);

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
                ? Password::min(12)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : null,
        );
    }
}
