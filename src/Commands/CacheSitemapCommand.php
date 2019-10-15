<?php

namespace Flagrow\Sitemap\Commands;

use Flagrow\Sitemap\SitemapGenerator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\View\Factory;

class CacheSitemapCommand extends Command
{
    protected $signature = 'flagrow:sitemap:cache';
    protected $description = 'Persists sitemap to disk and to cache.';

    public function handle(Factory $view, Store $cache, SitemapGenerator $generator)
    {
        $urlSet = $generator->getUrlSet();

        $cache->forever('flagrow.sitemap', $urlSet);

        @file_put_contents(
            public_path('sitemap.xml'),
            $view->make('flagrow-sitemap::sitemap')->with('urlset', $urlSet)->render()
        );
    }
}
