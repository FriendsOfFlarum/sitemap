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

namespace FoF\Sitemap\Generate;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use FoF\Sitemap\Deploy\DeployInterface;
use FoF\Sitemap\Exceptions\SetLimitReachedException;
use FoF\Sitemap\Resources\Resource;
use FoF\Sitemap\Sitemap\Sitemap;
use FoF\Sitemap\Sitemap\Url;
use FoF\Sitemap\Sitemap\UrlSet;
use Illuminate\Support\Collection;

class Generator
{
    public function __construct(
        protected DeployInterface $deploy,
        protected array $resources
    ) {
    }

    public function generate(): ?string
    {
        $now = Carbon::now();

        return $this->deploy->storeIndex(
            (new Sitemap($this->loop(), $now))->toXML()
        );
    }

    public function resources(): Collection
    {
        return Collection::make($this->resources)
            ->map(fn (string $class) => resolve($class))
            ->filter(fn (Resource $resource) => $resource->enabled())
            ->keyBy(fn (Resource $resource) => $resource->slug());
    }

    public function loop(): array
    {
        $set = new UrlSet();
        $remotes = [];
        $i = 0;

        foreach ($this->resources as $res) {
            /** @var resource $resource */
            $resource = resolve($res);

            if (!$resource->enabled()) {
                continue;
            }

            $resource
                ->query()
                ->each(function (AbstractModel $item) use (&$set, $resource, &$remotes, &$i) {
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

                        $set = new UrlSet();
                        $set->add($url);
                    }
                });

            $remotes[$i] = $this->deploy->storeSet($i, $set->toXml());

            $i++;

            $set = new UrlSet();
        }

        return $remotes;
    }
}
