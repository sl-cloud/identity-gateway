<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\TelescopeServiceProvider;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

return array_values(array_filter([
    AppServiceProvider::class,
    AuthServiceProvider::class,
    class_exists(TelescopeApplicationServiceProvider::class)
        ? TelescopeServiceProvider::class
        : null,
]));
