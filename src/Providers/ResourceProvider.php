<?php

namespace FoF\Sitemap\Providers;

use Flarum\Foundation\AbstractServiceProvider;
use FoF\Sitemap\Resources\Discussion;
use FoF\Sitemap\Resources\Page;
use FoF\Sitemap\Resources\Tag;
use FoF\Sitemap\Resources\User;

class ResourceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton('fof-sitemaps.resources', function () {
            return [
                Discussion::class,
                Page::class,
                Tag::class,
                User::class,
            ];
        });
    }
}
