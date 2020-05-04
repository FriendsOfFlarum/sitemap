<?php

namespace FoF\Sitemap\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use FoF\Sitemap\Resources\Resource;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class RegisterResource implements ExtenderInterface
{
    /**
     * @var string
     */
    private $resource;

    public function __construct(string $resource)
    {
        $this->resource = $resource;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->resolving('fof.sitemap.resources', function (array $resources) use ($container) {
            $resource = $container->make($this->resource);

            if ($resource instanceof Resource) {
                $resources[] = $resource;
            } else {
                throw new InvalidArgumentException("{$this->resource} has to extend " . Resource::class);
            }

            return $resources;
        });
    }
}
