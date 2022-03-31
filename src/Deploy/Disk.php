<?php

namespace FoF\Sitemap\Deploy;

use Flarum\Database\AbstractModel;
use FoF\Sitemap\Generate\Generator;
use FoF\Sitemap\Resources\Resource;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Database\Eloquent\Builder;

class Disk implements DeployInterface
{
    public function __construct(
        public Cloud $sitemapStorage,
        public Cloud $indexStorage
    ) {}

    public function store(Generator $generator): ?array
    {

    }

    public function url(): ?string
    {
        return $this->sitemapStorage->url('/');
    }
}
