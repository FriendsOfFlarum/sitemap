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

use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Generate\Generator;
use Illuminate\Console\Command;

class BuildSitemapCommand extends Command
{
    protected $signature = 'fof:sitemap:build';
    protected $description = 'Persists sitemap to cache or disk.';

    /** @var Paths */
    protected $paths;

    /** @var SettingsRepositoryInterface */
    protected $settings;

    public function handle(Generator $generator)
    {
        $generator->generate();
    }
}
