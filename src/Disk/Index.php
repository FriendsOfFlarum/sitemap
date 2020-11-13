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

use Flarum\Foundation\Paths;
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

    protected $paths;

    public function __construct(string $url, array $resources, Paths $paths)
    {
        $this->resources = $resources;
        $this->url = $url;
        $this->paths = $paths;
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
                $this->paths->storage.DIRECTORY_SEPARATOR.'sitemaps-processing/sitemaps'
            );

            $this->sitemaps = array_merge($this->sitemaps, $sitemap->write());
        }

        $this->saveIndexFile();
    }

    protected function saveIndexFile()
    {
        $stream = fopen($this->paths->storage.DIRECTORY_SEPARATOR.'sitemaps-processing/sitemap.xml', 'w+');

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
        if (!is_dir($this->paths->public.DIRECTORY_SEPARATOR.'sitemaps')) {
            mkdir($this->paths->public.DIRECTORY_SEPARATOR.'sitemaps');
        }

        foreach ($this->sitemaps as $sitemap => $_) {
            copy(
                $this->paths->storage.DIRECTORY_SEPARATOR."sitemaps-processing/sitemaps$sitemap",
                $this->paths->public.DIRECTORY_SEPARATOR."sitemaps$sitemap"
            );
        }

        copy(
            $this->paths->storage.DIRECTORY_SEPARATOR.'sitemaps-processing/sitemap.xml',
            $this->paths->public.DIRECTORY_SEPARATOR.'sitemap.xml'
        );
    }

    protected function saveHomepage()
    {
        $home = new Home($this->url, $this->paths->storage.DIRECTORY_SEPARATOR.'sitemaps-processing/sitemaps');

        $this->sitemaps = array_merge($this->sitemaps, $home->write());
    }
}
