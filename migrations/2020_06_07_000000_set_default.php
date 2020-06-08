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

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        /**
         * @var \Flarum\Settings\SettingsRepositoryInterface
         */
        $settings = app('flarum.settings');

        $settings->set('fof-sitemap.mode', 'run');
        $settings->set('fof-sitemap.frequency', 'daily');
    },
    'down' => function (Builder $schema) {
        $settings = app('flarum.settings');

        $settings->delete('fof-sitemap.mode');
        $settings->delete('fof-sitemap.frequency');
    },
];
