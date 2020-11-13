<?php

/*
 * This file is part of fof/sitemap.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 *  For the full copyright and license information, please view the LICENSE.md
 *  file that was distributed with this source code.
 *
 */

namespace FoF\Sitemap;

use Flarum\Extend;
use Flarum\Foundation\Application;
use FoF\Sitemap\Controllers\SitemapController;
use Illuminate\Contracts\Events\Dispatcher;

return [
    new \FoF\Components\Extend\AddFofComponents(),

    new \FoF\Console\Extend\EnableConsole(),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Routes('forum'))
        ->get('/sitemap.xml', 'fof-sitemap-index', SitemapController::class),

    new Extend\Locales(__DIR__.'/resources/locale'),

    function (Application $app, Dispatcher $events) {
        $app->register(Providers\ResourceProvider::class);
        $app->register(Providers\ConsoleProvider::class);
    },

    (new Extend\Console())->command(Commands\CacheSitemapCommand::class),
    (new Extend\Console())->command(Commands\MultiPageSitemapCommand::class),

    (new Extend\View())
        ->namespace('fof-sitemap', __DIR__.'/views'),
];
