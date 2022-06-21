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
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Deploy\DeployInterface;
use FoF\Sitemap\Deploy\Disk;
use FoF\Sitemap\Deploy\Memory;
use Illuminate\Contracts\Container\Container;
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
            $sitemaps = $filesystem->disk('flarum-sitemaps');

            return new Disk(
                $sitemaps,
                $sitemaps
            );
        });
    }
}
