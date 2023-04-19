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

use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\SlugManager;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Deploy\DeployInterface;
use FoF\Sitemap\Generate\Generator;
use FoF\Sitemap\Resources\Discussion;
use FoF\Sitemap\Resources\Page;
use FoF\Sitemap\Resources\Resource;
use FoF\Sitemap\Resources\StaticUrls;
use FoF\Sitemap\Resources\Tag;
use FoF\Sitemap\Resources\User;
use Illuminate\Contracts\Container\Container;

class Provider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton('fof-sitemaps.resources', function () {
            return [
                StaticUrls::class,
                Discussion::class,
                Page::class,
                Tag::class,
                User::class,
            ];
        });

        $this->container->singleton(Generator::class, function (Container $container) {
            return new Generator(
                $container->make(DeployInterface::class),
                $container->make('fof-sitemaps.resources')
            );
        });
    }

    public function boot()
    {
        Resource::setUrlGenerator($this->container->make(UrlGenerator::class));
        Resource::setSlugManager($this->container->make(SlugManager::class));
        Resource::setSettings($this->container->make(SettingsRepositoryInterface::class));
        Resource::setExtensionManager($this->container->make(ExtensionManager::class));
    }
}
