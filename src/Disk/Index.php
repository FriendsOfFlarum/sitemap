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

namespace FoF\Sitemap\Disk;

use FoF\Sitemap\Resources\Resource;

class Index
{
    /**
     * @var array|resource[]
     */
    protected $resources;

    protected $sitemaps = [];
    /**
     * @var string
     */
    private $url;

    public function __construct(string $url, array $resources)
    {
        $this->resources = $resources;
        $this->url = $url;
    }

    public function write()
    {
        $this->saveHomepage();

        foreach ($this->resources as $resource) {
            $builder = $resource->query();

            $sitemap = new Sitemap(
                $builder->getModel()->getTable(),
                $builder,
                function ($model) use ($resource) {
                    return (object) [
                        'location'        => $resource->url($model),
                        'changeFrequency' => $resource->frequency(),
                        'lastModified'    => $resource->lastModifiedAt($model),
                        'priority'        => $resource->priority(),
                    ];
                },
                storage_path('sitemaps-processing/sitemaps')
            );

            $this->sitemaps = array_merge($this->sitemaps, $sitemap->write());
        }

        $this->saveIndexFile();
    }

    protected function saveIndexFile()
    {
        $stream = fopen(storage_path('sitemaps-processing/sitemap.xml'), 'w+');

        fwrite(
            $stream,
            <<<'EOM'
<?xml version="1.0" encoding="UTF-8"?>
   <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
EOM
        );

        foreach ($this->sitemaps as $sitemap => $lastModified) {
            fwrite(
                $stream,
                <<<EOM
  <sitemap>
      <loc>{$this->url}/sitemaps{$sitemap}</loc>
      <lastmod>{$lastModified->toW3cString()}</lastmod>
   </sitemap>
EOM
            );
        }

        fwrite(
            $stream,
            <<<'EOM'
</sitemapindex>
EOM
        );

        fclose($stream);
    }

    public function publish()
    {
        if (!is_dir(public_path('sitemaps'))) {
            mkdir(public_path('sitemaps'));
        }

        foreach ($this->sitemaps as $sitemap => $_) {
            copy(
                storage_path("sitemaps-processing/sitemaps$sitemap"),
                public_path("sitemaps$sitemap")
            );
        }

        copy(
            storage_path('sitemaps-processing/sitemap.xml'),
            public_path('sitemap.xml')
        );
    }

    protected function saveHomepage()
    {
        $home = new Home($this->url, storage_path('sitemaps-processing/sitemaps'));

        $this->sitemaps = array_merge($this->sitemaps, $home->write());
    }
}
