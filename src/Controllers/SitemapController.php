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

use Flarum\Http\UrlGenerator;
use FoF\Sitemap\Deploy\DeployInterface;
use FoF\Sitemap\Generate\Generator;
use FoF\Sitemap\Resources\Resource;
use FoF\Sitemap\Sitemap\Url;
use FoF\Sitemap\Sitemap\UrlSet;
use Illuminate\Support\Carbon;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SitemapController implements RequestHandlerInterface
{
    public function __construct(
        protected Generator $generator,
        protected DeployInterface $deploy,
        protected UrlGenerator $url
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->deploy->indexIsStale(Carbon::now()->subDay())) {
            $index = $this->generateIndex();

            $this->deploy->storeIndex($index);
        } else {
            $index = $this->deploy->getIndex();
        }

        if ($index instanceof Uri) {
            return new Response\RedirectResponse($index);
        }

        if (is_string($index)) {
            return new Response\XmlResponse($index);
        }

        return new Response\EmptyResponse(404);
    }

    private function generateIndex(): string
    {
        $set = new UrlSet();

        $this->generator
            ->resources()
            ->map(function (Resource $resource) {
                $files = [];

                $max = $resource->maxId();

                if ($max === 0) {
                    return $files;
                }

                $i = 0;

                while ($i < $max) {
                    $files[] = $this->url
                        ->to('forum')
                        ->route('fof-sitemap-subset', [
                            'resource' => $resource->slug(),
                            'begin'    => $i + 1,
                            'end'      => $i += 50000,
                        ]);
                }

                return $files;
            })
            ->flatten()
            ->each(function (string $url) use ($set) {
                $set->add(new Url($url));
            });

        return $set->toXml();
    }
}
