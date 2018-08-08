<?php

namespace Flagrow\Sitemap\Listeners;

use Flagrow\Sitemap\Controllers\SitemapController;
use Flarum\Event\ConfigureForumRoutes;
use Illuminate\Contracts\Events\Dispatcher;

class AddRoutes
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureForumRoutes::class, [$this, 'routes']);
    }

    public function routes(ConfigureForumRoutes $routes)
    {
        $routes->get('/sitemap.xml', 'flagrow-sitemap-index', SitemapController::class);
    }
}
