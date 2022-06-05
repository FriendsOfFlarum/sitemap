<?php

namespace FoF\Sitemap\Generate;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use FoF\Sitemap\Deploy\DeployInterface;
use FoF\Sitemap\Exceptions\SetLimitReachedException;
use FoF\Sitemap\Resources\Resource;
use FoF\Sitemap\Sitemap\Sitemap;
use FoF\Sitemap\Sitemap\Url;
use FoF\Sitemap\Sitemap\UrlSet;

class Generator
{
    public function __construct(
        protected DeployInterface $deploy,
        protected array $resources
    ) {}

    public function generate(): ?string
    {
        $now = Carbon::now();

        return $this->deploy->storeIndex(
            (new Sitemap($this->loop(), $now))->toXML()
        );
    }

    public function loop(): array
    {
        $set = new UrlSet;
        $remotes = [];
        $i = 0;

        foreach ($this->resources as $res) {
            /** @var Resource $resource */
            $resource = resolve($res);

            if (! $resource->enabled()) continue;

            $resource
                ->query()
                ->each(function(AbstractModel $item) use (&$set, $resource, &$remotes, &$i) {
                    $url = new Url(
                        $resource->url($item),
                        $resource->lastModifiedAt($item),
                        $resource->frequency(),
                        $resource->priority()
                    );

                    try {
                        $set->add($url);
                    } catch (SetLimitReachedException $e) {
                        $remotes[$i] = $this->deploy->storeSet($i, $set->toXml());

                        $i++;

                        $set = new UrlSet;
                        $set->add($url);
                    }
                });

            $remotes[$i] = $this->deploy->storeSet($i, $set->toXml());

            $i++;

            $set = new UrlSet;
        }

        return $remotes;
    }
}
