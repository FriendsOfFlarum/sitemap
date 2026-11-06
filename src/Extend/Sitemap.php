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

namespace FoF\Sitemap\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use FoF\Sitemap\Resources\Resource;
use FoF\Sitemap\Resources\StaticUrls;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class Sitemap implements ExtenderInterface
{
    private array $resourcesToAdd = [];
    private array $resourcesToRemove = [];
    private array $resourcesToReplace = [];
    private array $queryModifiers = [];
    private array $staticUrls = [];
    private bool $forceCached = false;

    /**
     * Add a resource to the sitemap. Specify the ::class of the resource.
     * Resource must extend FoF\Sitemap\Resources\Resource.
     *
     * @param string $resource
     *
     * @return self
     */
    public function addResource(string $resource): self
    {
        $this->validateResource($resource);
        $this->resourcesToAdd[] = $resource;

        return $this;
    }

    /**
     * Remove a resource from the sitemap. Specify the ::class of the resource.
     *
     * @param string $resource
     *
     * @return self
     */
    public function removeResource(string $resource): self
    {
        $this->resourcesToRemove[] = $resource;

        return $this;
    }

    /**
     * Replace an existing resource with a new one. Specify the ::class of both resources.
     * Both resources must extend FoF\Sitemap\Resources\Resource.
     *
     * @param string $oldResource The resource to replace
     * @param string $newResource The replacement resource
     *
     * @return self
     */
    public function replaceResource(string $oldResource, string $newResource): self
    {
        $this->validateResource($newResource);
        $this->resourcesToReplace[$oldResource] = $newResource;

        return $this;
    }

    /**
     * Modify the query of a resource before the sitemap is built.
     * Applies to the given resource class and all of its subclasses, including replacements registered
     * via replaceResource() that extend it. Modifiers from all extensions stack and run in registration order.
     *
     * The modifier receives the result of Resource::query() (a Builder, or a Collection for StaticUrls) and the
     * resource instance. A Builder can be modified in place; any non-null return value replaces the query.
     * The modifier can be a callable or the ::class of an invokable class, which is resolved from the container.
     *
     * @param class-string<resource> $resourceClass
     * @param callable|class-string  $modifier
     *
     * @return self
     */
    public function modifyResourceQuery(string $resourceClass, callable|string $modifier): self
    {
        $this->validateResource($resourceClass);
        $this->queryModifiers[] = [$resourceClass, $modifier];

        return $this;
    }

    /**
     * Add a static URL to the sitemap. Specify the route name.
     *
     * @param string $routeName
     *
     * @return self
     */
    public function addStaticUrl(string $routeName): self
    {
        $this->staticUrls[] = $routeName;

        return $this;
    }

    /**
     * Force cached mode, disabling runtime mode and any other modes.
     * Intended for use in managed hosting.
     *
     * @return self
     */
    public function forceCached(): self
    {
        $this->forceCached = true;

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        if (!empty($this->resourcesToAdd) || !empty($this->resourcesToRemove) || !empty($this->resourcesToReplace)) {
            $container->extend('fof-sitemaps.resources', function (array $resources) {
                // Replace existing resources
                if (!empty($this->resourcesToReplace)) {
                    foreach ($this->resourcesToReplace as $oldResource => $newResource) {
                        $key = array_search($oldResource, $resources);
                        if ($key !== false) {
                            $resources[$key] = $newResource;
                        }
                    }
                }

                // Add new resources
                foreach ($this->resourcesToAdd as $resource) {
                    $resources[] = $resource;
                }

                // Remove specified resources
                if (!empty($this->resourcesToRemove)) {
                    $resources = array_filter($resources, function ($res) {
                        return !in_array($res, $this->resourcesToRemove);
                    });
                }

                return array_values($resources); // Re-index array
            });
        }

        if (!empty($this->queryModifiers)) {
            $container->extend('fof-sitemaps.queryModifiers', function (array $modifiers) use ($container) {
                foreach ($this->queryModifiers as [$resourceClass, $modifier]) {
                    $modifiers[] = [$resourceClass, ContainerUtil::wrapCallback($modifier, $container)];
                }

                return $modifiers;
            });
        }

        // Register static URLs
        foreach ($this->staticUrls as $routeName) {
            StaticUrls::addRoute($routeName);
        }

        // Force cached mode if requested
        if ($this->forceCached) {
            $container->instance('fof-sitemaps.forceCached', true);
        }
    }

    private function validateResource(string $resource): void
    {
        foreach (class_parents($resource) as $class) {
            if ($class === Resource::class) {
                return;
            }
        }

        throw new InvalidArgumentException("{$resource} has to extend ".Resource::class);
    }
}
