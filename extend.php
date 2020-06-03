<?php

namespace FoF\Sitemap;

use FoF\Sitemap\Controllers\SitemapController;
use Flarum\Extend;
use Flarum\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;

return [
    new \FoF\Console\Extend\EnableConsole(),

    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),

    (new Extend\Routes('forum'))
        ->get('/sitemap.xml', 'fof-sitemap-index', SitemapController::class),

    new Extend\Locales(__DIR__ . '/resources/locale'),

    function (Application $app, Dispatcher $events) {
        $app->register(Providers\ResourceProvider::class);
        $app->register(Providers\ViewProvider::class);
    },

    (new Extend\Console())->command(Commands\CacheSitemapCommand::class),
    (new Extend\Console())->command(Commands\MultiPageSitemapCommand::class),
];
