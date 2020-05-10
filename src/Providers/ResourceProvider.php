<?php

namespace FoF\Sitemap\Providers;

use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\AbstractServiceProvider;
use FoF\Sitemap\Resources;

class ResourceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->app->singleton('fof.sitemap.resources', function () {
            $resources = [
                new Resources\User,
                new Resources\Discussion
            ];

            /** @var ExtensionManager $extensions */
            $extensions = $this->app->make(ExtensionManager::class);

            if ($extensions->isEnabled('flarum-tags')) {
                $resources[] = new Resources\Tag;
            }
            if ($extensions->isEnabled('fof-pages')) {
                $resources[] = new Resources\Page;
            }

            return $resources;
        });
    }
}
