<?php

namespace FoF\Sitemap;

use FoF\Sitemap\Controllers\SitemapController;
use Flarum\Console\Event\Configuring;
use Flarum\Extend;
use Flarum\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;

return [
    (new Extend\Routes('forum'))
        ->get('/sitemap.xml', 'fof-sitemap-index', SitemapController::class),
    function (Application $app, Dispatcher $events) {
        $app->register(Providers\ResourceProvider::class);
        $app->register(Providers\ViewProvider::class);

        $events->listen(Configuring::class, function (Configuring $event) {
            $event->addCommand(Commands\CacheSitemapCommand::class);
            $event->addCommand(Commands\MultiPageSitemapCommand::class);
        });
    },
];
