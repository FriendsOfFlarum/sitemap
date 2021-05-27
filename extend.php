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
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Controllers\SitemapController;
use Illuminate\Console\Scheduling\Event;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Routes('forum'))
        ->get('/sitemap.xml', 'fof-sitemap-index', SitemapController::class),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\ServiceProvider())
        ->register(Providers\ResourceProvider::class),

    (new Extend\Console())
        ->command(Commands\BuildSitemapCommand::class)
        ->schedule(Commands\BuildSitemapCommand::class, function (Event $event) {
            /** @var SettingsRepositoryInterface */
            $settings = resolve(SettingsRepositoryInterface::class);
            $frequency = $settings->get('fof-sitemap.frequency');

            $event->withoutOverlapping();
            switch ($frequency) {
                case 'twice-daily':
                    $event->twiceDaily();
                    break;
                case 'hourly':
                    $event->hourly();
                    break;
                default:
                    $event->daily();
                    break;
            }
        }),

    (new Extend\View())
        ->namespace('fof-sitemap', __DIR__.'/views'),
];
