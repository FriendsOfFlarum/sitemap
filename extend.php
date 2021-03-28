<?php

/*
 * This file is part of fof/sitemap.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 */

namespace FoF\Sitemap;

use Flarum\Extend;
use FoF\Sitemap\Controllers\SitemapController;

return [
    new \FoF\Console\Extend\EnableConsole(),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Routes('forum'))
        ->get('/sitemap.xml', 'fof-sitemap-index', SitemapController::class),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\ServiceProvider())
        ->register(Providers\ResourceProvider::class)
        ->register(Providers\ConsoleProvider::class),

    (new Extend\Console())->command(Commands\CacheSitemapCommand::class),
    (new Extend\Console())->command(Commands\MultiPageSitemapCommand::class),

    (new Extend\View())
        ->namespace('fof-sitemap', __DIR__.'/views'),
];
