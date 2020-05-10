<?php

namespace FoF\Sitemap;

use Carbon\Carbon;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Application;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Resources\Resource;
use FoF\Sitemap\Sitemap\Frequency;
use FoF\Sitemap\Sitemap\UrlSet;

class SitemapGenerator
{
    protected $app;
    protected $extensions;

    public function __construct(Application $app, ExtensionManager $extensions)
    {
        $this->app = $app;
        $this->extensions = $extensions;
    }

    public function getUrlSet()
    {
        $urlSet = new UrlSet();

        $url = $this->app->url();

        // Always add the homepage, whichever it is
        $urlSet->addUrl($url . '/', Carbon::now(), Frequency::DAILY, 0.9);

        /** @var SettingsRepositoryInterface $settings */
        $settings = $this->app->make(SettingsRepositoryInterface::class);

        // If the homepage is different from /all, also add /all
        if ($settings->get('default_route') !== '/all') {
            $urlSet->addUrl($url . '/all', Carbon::now(), Frequency::DAILY, 0.9);
        }

        $resources = $this->app->make('fof.sitemap.resources') ?? [];

        /** @var Resource $resource */
        foreach ($resources as $resource) {
            $resource->query()->each(function ($model) use (&$urlSet, $resource) {
                $urlSet->addUrl(
                    $resource->url($model),
                    $resource->lastModifiedAt($model),
                    $resource->frequency(),
                    $resource->priority()
                );
            });
        }

        return $urlSet;
    }
}
