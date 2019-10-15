<?php

namespace Flagrow\Sitemap\Commands;

use Flagrow\Sitemap\SitemapGenerator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\View\Factory;

class CacheSitemapCommand extends Command
{
    protected $signature = 'flagrow:sitemap:cache {--write-xml-file : write to sitemap.xml}';
    protected $description = 'Persists sitemap to cache and optionally to disk.';

    public function handle(Factory $view, Store $cache, SitemapGenerator $generator)
    {
        $urlSet = $generator->getUrlSet();

        $cache->forever('flagrow.sitemap', $urlSet);

        if ($this->option('write-xml-file')) {
            @file_put_contents(
                public_path('sitemap.xml'),
                $view->make('flagrow-sitemap::sitemap')->with('urlset', $urlSet)->render()
            );
        }
    }
}
