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

    public function handle(Paths $paths, SettingsRepositoryInterface $settings)
    {
        $this->paths = $paths;
        $this->settings = $settings;

        /**
         * Possible values:
         * `run` -> Runtime mode, no action required here
         * `cache` -> in memory caching of sitemap.xml
         * `cache-disk` -> write sitemap.xml to disk
         * `multi-file` -> write the sitemap as multi-part files on disk.
         *
         * @var string $mode
         */
        $mode = $this->settings->get('fof-sitemap.mode', 'run');
        $this->info("FoF Sitemap: running in $mode mode");

        switch ($mode) {
            case 'cache':
                $this->cache();
                break;
            case 'cache-disk':
                $this->cache(true);
                break;
            case 'multi-file':
                $this->multi();
                break;
            default:
                $this->info('FoF Sitemap: Nothing to generate in this mode');
                $this->forgetAll();

                return;
        }
    }

    private function forgetAll(): void
    {
        $this->forgetCache();

        $this->forgetDisk();

        $this->forgetMulti();
    }

    private function forgetCache(): bool
    {
        /** @var Store */
        $cache = resolve(Store::class);

        return $cache->forget('fof-sitemap');
    }

    private function forgetDisk(): bool
    {
        if (file_exists($file = $this->paths->public.DIRECTORY_SEPARATOR.'sitemap.xml')) {
            return unlink($file);
        }

        return false;
    }

    private function forgetMulti(): bool
    {
        if (file_exists($dir = $this->paths->public.DIRECTORY_SEPARATOR.'sitemaps')) {
            foreach (glob($dir.'/*.*') as $filename) {
                if (is_file($filename)) {
                    unlink($filename);
                }
            }

            return rmdir($dir);
        }

        return false;
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

            $this->forgetCache();
            $this->forgetMulti();

            $this->info('FoF Sitemap: disk mode complete');
        } else {
            $this->forgetDisk();
            $this->forgetMulti();
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

        $this->forgetCache();

        $this->info('FoF Sitemap: multi mode complete');
    }
}
