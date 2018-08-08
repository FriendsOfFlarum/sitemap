<?php

namespace Flagrow\Sitemap;

use Flarum\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;

return function (Application $app, Dispatcher $events) {
    $app->register(Providers\ViewProvider::class);

    $events->subscribe(Listeners\AddRoutes::class);
};
