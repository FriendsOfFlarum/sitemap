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

namespace FoF\Sitemap\Commands;

use Flarum\Foundation\Config;
use Flarum\Foundation\Paths;
use FoF\Sitemap\Disk\Index;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;

class MultiPageSitemapCommand extends Command
{
    protected $signature = 'fof:sitemap:multi';
    protected $description = 'Persists sitemap to disk into multiple gzipped files.';

    public function handle(Config $config, Container $container, Paths $paths)
    {
        $index = new Index(
            $config->url(),
            $container->make('fof.sitemap.resources') ?? [],
            $paths
        );

        $index->write();

        $index->publish();
    }
}
