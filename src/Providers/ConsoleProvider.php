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

namespace FoF\Sitemap\Providers;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Scheduling\Schedule;

class ConsoleProvider extends AbstractServiceProvider
{
    public function register()
    {
        if (!defined('ARTISAN_BINARY')) {
            define('ARTISAN_BINARY', 'flarum');
        }

        $settings = $this->app->make(SettingsRepositoryInterface::class);

        $mode = $settings->get('fof-sitemap.mode');
        if (empty($mode) || $mode === 'run') {
            return;
        }

        $this->app->resolving(Schedule::class, function (Schedule $schedule) use ($mode) {
            switch ($mode) {
                case 'multi-file':
                    $command = 'fof:sitemap:multi';
                    break;
                case 'cache':
                    $command = 'fof:sitemap:cache';
                    break;
                case 'cache-disk':
                    $command = 'fof:sitemap:cache --write-xml-file';
                    break;
                default:
                    return;
            }

            $schedule->command($command)
                ->dailyAt('02:00')
                ->withoutOverlapping();
        });
    }
}
