<?php

/*
 * This file is part of fof/sitemap.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 */

namespace FoF\Sitemap\Disk;

use Flarum\Foundation\Paths;
use FoF\Sitemap\Resources\Resource;
use FoF\Sitemap\Storage\StorageInterface;

class Index extends Disk
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

    public function write(): array
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
                }
            );

            $this->sitemaps = array_merge($this->sitemaps, $sitemap->write());
        }

        return $this->sitemaps;
    }

    protected function getIndex()
    {
        $fs = static::getTemporaryFilesystem();

        $path = "sitemap.xml";

        $fs->put(
            $path,
            <<<'EOM'
<?xml version="1.0" encoding="UTF-8"?>
   <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
EOM
        );

        foreach ($this->sitemaps as $sitemap => $lastModified) {
            $url = static::getTargetFilesystem()->url($sitemap);

            $fs->append(
                $path,
                <<<EOM
  <sitemap>
      <loc>$url</loc>
      <lastmod>{$lastModified->toW3cString()}</lastmod>
   </sitemap>
EOM
            );
        }

        $fs->append(
            $path,
            <<<'EOM'
</sitemapindex>
EOM
        );
    }

    public function publish()
    {
        /** @var StorageInterface $storage */
        $storage = resolve('fof.sitemap.storage');

        $storage->publish($this->sitemaps);
    }

    protected function saveHomepage()
    {
        $home = new Home($this->url);

        $this->sitemaps = array_merge($this->sitemaps, $home->write());
    }
}
