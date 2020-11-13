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

use Flarum\Foundation\Paths;
use FoF\Sitemap\SitemapGenerator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\View\Factory;

class CacheSitemapCommand extends Command
{
    protected $signature = 'fof:sitemap:cache {--write-xml-file : write to sitemap.xml}';
    protected $description = 'Persists sitemap to cache and optionally to disk.';

    public function handle(Factory $view, Store $cache, SitemapGenerator $generator, Paths $paths)
    {
        $urlSet = $generator->getUrlSet();

        $cache->forever('fof-sitemap', $urlSet);

        if ($this->option('write-xml-file')) {
            @file_put_contents(
                $paths->public.DIRECTORY_SEPARATOR.'sitemap.xml',
                $view->make('fof-sitemap::sitemap')->with('urlset', $urlSet)->render()
            );
        }
    }
}
