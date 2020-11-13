<?php

/*
 * This file is part of fof/sitemap.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 *  For the full copyright and license information, please view the LICENSE.md
 *  file that was distributed with this source code.
 *
 */

namespace FoF\Sitemap;

use Carbon\Carbon;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Sitemap\Frequency;
use FoF\Sitemap\Sitemap\UrlSet;
use Illuminate\Contracts\Container\Container;

class SitemapGenerator
{
    protected $app;
    protected $extensions;
    protected $settings;
    protected $url;

    public function __construct(Container $app, ExtensionManager $extensions, SettingsRepositoryInterface $settings, UrlGenerator $url)
    {
        $this->app = $app;
        $this->extensions = $extensions;
        $this->settings = $settings;
        $this->url = $url;
    }

    public function getUrlSet()
    {
        $urlSet = new UrlSet();

        // Always add the homepage, whichever it is
        $urlSet->addUrl($this->url->to('forum')->base().'/', Carbon::now(), Frequency::DAILY, 0.9);

        // If the homepage is different from /all, also add /all
        if ($this->settings->get('default_route') !== '/all') {
            $urlSet->addUrl($this->url->to('forum')->route('index'), Carbon::now(), Frequency::DAILY, 0.9);
        }

        $resources = $this->app->make('fof.sitemap.resources') ?? [];

        /** @var \FoF\Sitemap\Resources\Resource $resource */
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
