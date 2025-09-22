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

use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Extend;
use Flarum\Foundation\Paths;
use Flarum\Http\UrlGenerator;
use FoF\Sitemap\Extend\Robots;
use FoF\Sitemap\Robots\Entries\TagEntry;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Routes('forum'))
        ->get('/sitemap.xml', 'fof-sitemap-index', Controllers\SitemapController::class)
        ->get('/sitemap-{id:\d+}.xml', 'fof-sitemap-set', Controllers\SitemapController::class)
        // Remove the robots.txt route added by v17development/flarum-seo to avoid conflicts.
        // This is so this extension can handle the robots.txt generation instead.
        // We can safely remove this without a conditional, as the remove() function will simply do nothing if the route does not exist.
        // TODO: Reach out to v17development to see if they want to drop robots.txt generation from their extension.
        ->remove('v17development-flarum-seo')
        ->get('/robots.txt', 'fof-sitemap-robots-index', Controllers\RobotsController::class),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attributes(ForumAttributes::class),

    (new Extend\ServiceProvider())
        ->register(Providers\Provider::class)
        ->register(Providers\DeployProvider::class)
        ->register(Providers\RobotsProvider::class),

    (new Extend\Console())
        ->command(Console\BuildSitemapCommand::class)
        ->schedule(Console\BuildSitemapCommand::class, new Console\BuildSitemapSchedule()),

    (new Extend\View())
        ->namespace('fof-sitemap', __DIR__.'/views'),

    (new Extend\Filesystem())
        ->disk('flarum-sitemaps', function (Paths $paths, UrlGenerator $url) {
            return [
                'root'   => "$paths->public/sitemaps",
                'url'    => $url->to('forum')->path('sitemaps'),
            ];
        }),

    (new Extend\Settings())
        ->default('fof-sitemap.mode', 'run')
        ->default('fof-sitemap.frequency', 'daily')
        ->default('fof-sitemap.excludeUsers', false)
        ->default('fof-sitemap.excludeTags', false)
        ->default('fof-sitemap.model.user.comments.minimum_item_threshold', 5)
        ->default('fof-sitemap.model.tags.discussion.minimum_item_threshold', 5)
        ->default('fof-sitemap.include_priority', true)
        ->default('fof-sitemap.include_changefreq', true),

    (new Extend\Event())
        ->subscribe(Listeners\SettingsListener::class),

    // Conditionally add TagEntry only when flarum/tags extension is enabled
    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-tags', fn() => [
            (new Robots())
                ->addEntry(TagEntry::class)
        ]),
];
