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

namespace FoF\Sitemap\Console;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Scheduling\Event;

class BuildSitemapSchedule
{
    public function __invoke(Event $event)
    {
        /** @var SettingsRepositoryInterface */
        $settings = resolve(SettingsRepositoryInterface::class);
        $frequency = $settings->get('fof-sitemap.frequency');

        $event->onOneServer()
            ->withoutOverlapping();

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
    }
}
