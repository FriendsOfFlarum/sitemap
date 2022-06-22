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

namespace FoF\Sitemap\Controllers;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use FoF\Sitemap\Deploy\DeployInterface;
use FoF\Sitemap\Generate\Generator;
use FoF\Sitemap\Resources\Resource;
use FoF\Sitemap\Sitemap\Url;
use FoF\Sitemap\Sitemap\UrlSet;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SitemapSubsetController implements RequestHandlerInterface
{
    public function __construct(
        protected DeployInterface $deploy,
        protected Generator $generator
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $slug = Arr::get($request->getQueryParams(), 'resource');
        $begin = Arr::get($request->getQueryParams(), 'begin');
        $end = Arr::get($request->getQueryParams(), 'end');

        $resource = $this->generator->resources()->get($slug);
        $set = null;

        $index = "$slug-$begin-$end";

        if ($resource && $this->deploy->indexIsStale(Carbon::now()->subDay())) {
            $set = $this->generateSubset($resource, $begin, $end);
            $this->deploy->storeSet($index, $set);
        } elseif($resource) {
            $set = $this->deploy->getSet($index);
        }

        if ($set instanceof Uri) {
            return new Response\RedirectResponse($set);
        }

        if (is_string($set)) {
            return new Response\XmlResponse($set);
        }

        return new Response\EmptyResponse(404);
    }

    private function generateSubset(Resource $resource, int $begin, int $end): string
    {
        $set = new UrlSet;

        $resource
            ->query()
            ->whereBetween(
                $resource->query()->getModel()->getKeyName(),
                [$begin, $end]
            )
            ->each(function (AbstractModel $item) use ($resource, $set) {
                $set->add(new Url(
                    $resource->url($item),
                    $resource->lastModifiedAt($item),
                    $resource->frequency(),
                    $resource->priority()
                ));
            });

        return $set->toXml();
    }
}
