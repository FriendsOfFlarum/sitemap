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

namespace FoF\Sitemap\Commands;

use Flarum\Foundation\Config;
use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Disk\Index;
use FoF\Sitemap\SitemapGenerator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory;

class BuildSitemapCommand extends Command
{
    protected $signature = 'fof:sitemap:build';
    protected $description = 'Persists sitemap to cache or disk.';

    /** @var Paths */
    protected $paths;

    /** @var SettingsRepositoryInterface */
    protected $settings;

    public function handle(Factory $view, Store $cache, SitemapGenerator $generator, Paths $paths, SettingsRepositoryInterface $settings)
    {
        $this->paths = $paths;
        $this->settings = $settings;

        /**
         * Possible values:
         * `run` -> Runtime mode, no action required here
         * `cache` -> in memory caching of sitemap.xml
         * `disk` -> write sitemap.xml to disk
         * `multi` -> write the sitemap as multi-part files on disk.
         *
         * @var string $mode
         */
        $mode = $this->settings->get('fof-sitemap.mode');

        switch ($mode) {
            case 'cache':
                $this->cache();
                break;
            case 'disk':
                $this->cache(true);
                break;
            case 'multi':
                $this->multi();
                break;
            default:
                $this->info('FoF Sitemap: Nothing to do in this mode');

                return;
        }

        $this->info("FoF Sitemap: running in $mode mode");
    }

    private function cache(bool $toDisk = false): void
    {
        /** @var Factory */
        $view = resolve(Factory::class);
        /** @var Store */
        $cache = resolve(Store::class);
        /** @var SitemapGenerator */
        $generator = resolve(SitemapGenerator::class);

        $urlSet = $generator->getUrlSet();

        $cache->forever('fof-sitemap', $urlSet);

        if ($toDisk) {
            @file_put_contents(
                $this->paths->public.DIRECTORY_SEPARATOR.'sitemap.xml',
                $view->make('fof-sitemap::sitemap')->with('urlset', $urlSet)->render()
            );

            $this->info('FoF Sitemap: disk mode complete');
        } else {
            $this->info('FoF Sitemap: cache mode complete');
        }
    }

    private function multi(): void
    {
        /** @var Config */
        $config = resolve(Config::class);
        /** @var Container */
        $container = resolve(Container::class);

        $index = new Index(
            $config->url(),
            $container->make('fof.sitemap.resources') ?? [],
            $this->paths
        );

        $index->write();

        $index->publish();

        $this->info('FoF Sitemap: multi mode complete');
    }
}
