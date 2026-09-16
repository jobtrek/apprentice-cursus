<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use \SocialiteProviders\Manager\ServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    ServiceProvider::class,
];
