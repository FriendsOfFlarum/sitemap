<?php

namespace FoF\Sitemap\Modes;

use FoF\Sitemap\Generator;
use Illuminate\Contracts\Filesystem\Cloud;

class Disk implements ModeInterface
{
    public function __construct(
        public Cloud $sitemapStorage,
        public Cloud $indexStorage)
    {}

    public function store(Generator $generator): void
    {

    }

    public function url(): ?string
    {
        return $this->sitemapStorage->url('/');
    }


}
