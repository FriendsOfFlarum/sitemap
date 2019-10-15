<?php

namespace Flagrow\Sitemap;

use Flagrow\Sitemap\Controllers\SitemapController;
use Flarum\Console\Event\Configuring;
use Flarum\Extend;
use Flarum\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Routes('forum'))
        ->get('/sitemap.xml', 'flagrow-sitemap-index', SitemapController::class),
    function (Application $app, Dispatcher $events) {
        $app->register(Providers\ViewProvider::class);

        $events->listen(Configuring::class, function (Configuring $event) {
            $event->addCommand(Commands\CacheSitemapCommand::class);
        });
    },
];
