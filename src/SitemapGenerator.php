<?php

namespace FoF\Sitemap;

use Carbon\Carbon;
use FoF\Sitemap\Resources\Resource;
use FoF\Sitemap\Sitemap\Frequency;
use FoF\Sitemap\Sitemap\UrlSet;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Application;
use Sijad\Pages\Page;

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

        $urlSet->addUrl($url . '/', Carbon::now(), Frequency::DAILY, 0.9);

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
