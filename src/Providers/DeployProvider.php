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

namespace FoF\Sitemap\Providers;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Deploy\DeployInterface;
use FoF\Sitemap\Deploy\Disk;
use FoF\Sitemap\Deploy\Memory;
use FoF\Sitemap\Deploy\ProxyDisk;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;

class DeployProvider extends AbstractServiceProvider
{
    public function register()
    {
        // Create a sane default for storing sitemap files.
        $this->container->singleton(DeployInterface::class, function (Container $container) {
            /** @var SettingsRepositoryInterface $settings */
            $settings = $this->container->make(SettingsRepositoryInterface::class);

            $mode = $settings->get('fof-sitemap.mode');

            if ($mode === 'run' && !$this->container->bound('fof-sitemaps.forceCached')) {
                return $this->container->make(Memory::class);
            }

            // For legacy reasons, if the $mode check is ever updated, it needs to handle `cache`, `cache-disk` and `multi-file` with Disk

            /** @var Factory $filesystem */
            $filesystem = $container->make(Factory::class);
            /** @var Cloud $sitemaps */
            $sitemaps = $filesystem->disk('flarum-sitemaps');

            // Check if storage URL matches Flarum's base URL
            if ($this->needsProxy($sitemaps, $container)) {
                return new ProxyDisk(
                    $sitemaps,
                    $sitemaps,
                    $container->make(UrlGenerator::class)
                );
            }

            return new Disk(
                $sitemaps,
                $sitemaps
            );
        });
    }

    private function needsProxy(Cloud $disk, Container $container): bool
    {
        // Get Flarum's configured base URL
        /** @var Config $config */
        $config = $container->make(Config::class);
        $baseUrl = parse_url($config->url(), PHP_URL_HOST);

        // Get a sample URL from the storage disk
        $storageUrl = $disk->url('test.xml');
        $storageHost = parse_url($storageUrl, PHP_URL_HOST);

        // If hosts don't match, we need to proxy
        return $baseUrl !== $storageHost;
    }
}
