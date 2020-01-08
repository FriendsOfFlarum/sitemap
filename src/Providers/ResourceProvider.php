<?php

namespace Flagrow\Sitemap\Providers;

use Flagrow\Sitemap\Resources;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Tags\Tag;

class ResourceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->app->singleton('flagrow.sitemap.resources', function () {
            return [
                new Resources\User,
                new Resources\Discussion
            ];
        });

        $this->app->resolving('flagrow.sitemap.resources', function (array $resources) {
            /** @var ExtensionManager $extensions */
            $extensions = $this->app->make(ExtensionManager::class);

            if ($extensions->isEnabled('flarum-tags') && class_exists(Tag::class)) {
                $resources[] = new Resources\Tag;
            }
            if ($extensions->isEnabled('fof-pages')) {
                $resources[] = new Resources\Page;
            }
        });
    }
}
