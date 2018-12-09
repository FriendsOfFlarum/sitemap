<?php

namespace Flagrow\Sitemap;

use Flagrow\Sitemap\Controllers\SitemapController;
use Flarum\Extend;
use Flarum\Foundation\Application;

return [
    (new Extend\Routes('forum'))
        ->get('/sitemap.xml', 'flagrow-sitemap-index', SitemapController::class),
    function (Application $app) {
        $app->register(Providers\ViewProvider::class);
    },
];
