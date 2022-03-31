<?php

namespace FoF\Sitemap\Generate;

use Flarum\Database\AbstractModel;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Deploy\DeployInterface;
use FoF\Sitemap\Exceptions\SetLimitReachedException;
use FoF\Sitemap\Resources\Resource;
use FoF\Sitemap\Sitemap\Url;
use FoF\Sitemap\Sitemap\UrlSet;

class Generator
{
    public function __construct(
        protected DeployInterface $deploy,
        protected array $resources,
        protected SettingsRepositoryInterface $settings
    ) {}

    public function generate()
    {
    }

    public function loop()
    {
        $set = new UrlSet;
        $remotes = [];

        foreach ($this->resources as $res) {
            /** @var Resource $resource */
            $resource = resolve($res);

            $resource
                ->query()
                ->each(function(AbstractModel $item) use (&$set, $resource, &$remotes) {
                    $url = new Url(
                        $resource->url($item),
                        $resource->lastModifiedAt($item),
                        $resource->frequency(),
                        $resource->priority()
                    );

                    try {
                        $set->add($url);
                    } catch (SetLimitReachedException $e) {
                        $remotes[] = $this->deploy->store($set);

                        $set = new UrlSet;
                        $set->add($url);
                    }
                });

            $remotes[] = $this->deploy->store($set);
        }
    }
}
