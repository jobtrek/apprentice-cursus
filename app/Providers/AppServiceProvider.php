<?php

namespace App\Providers;

use App\Models\Grade;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        // Short aliases keep commentable_type stable if a model is ever renamed or moved.
        // enforceMorphMap() also throws on any model used polymorphically that is missing
        // here — so anything adding a morph relation later (database notifications via
        // Notifiable, an activity log, a media library) must be added to this map.
        Relation::enforceMorphMap([
            'grade' => Grade::class,
            'project' => Project::class,
        ]);

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
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
