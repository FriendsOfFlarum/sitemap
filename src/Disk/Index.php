<?php

namespace Flagrow\Sitemap\Disk;

use Carbon\Carbon;
use Flagrow\Sitemap\Resources\Resource;

class Index
{
    /**
     * @var array|Resource[]
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
                        'location' => $resource->url($model),
                        'changeFrequency' => $resource->frequency(),
                        'lastModified' => $resource->lastModifiedAt($model),
                        'priority' => $resource->priority()
                    ];
                },
                storage_path('sitemaps-processing/sitemaps')
            );

            array_push($this->sitemaps, ...$sitemap->write());
        }

        $this->saveIndexFile();
    }

    protected function saveIndexFile()
    {
        $now = Carbon::now()->toW3cString();

        $stream = fopen(storage_path('sitemaps-processing/sitemap.xml'), 'w+');

        fwrite($stream, <<<EOM
<?xml version="1.0" encoding="UTF-8"?>
   <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
EOM
        );

        foreach ($this->sitemaps as $sitemap) {
            fwrite($stream, <<<EOM
  <sitemap>
      <loc>{$this->url}/sitemaps/{$sitemap}</loc>
      <lastmod>{$now}</lastmod>
   </sitemap>
EOM
            );
        }

        fwrite($stream, <<<EOM
</sitemapindex>
EOM
        );

        fclose($stream);
    }

    public function publish()
    {
        copy(
            storage_path('sitemaps-processing/sitemap.xml'),
            public_path('sitemap.xml')
        );

        foreach ($this->sitemaps as $sitemap) {
            copy(
                storage_path("sitemaps-processing/sitemaps$sitemap"),
                public_path("sitemaps$sitemap")
            );
        }
    }

    protected function saveHomepage()
    {
        $home = new Home($this->url, storage_path('sitemaps-processing/sitemaps'));

        array_push($this->sitemaps, ...$home->write());
    }
}
