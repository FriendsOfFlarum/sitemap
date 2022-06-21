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

use Flarum\Http\Exception\RouteNotFoundException;
use FoF\Sitemap\Deploy\DeployInterface;
use FoF\Sitemap\Deploy\Memory;
use FoF\Sitemap\Generate\Generator;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MemoryController implements RequestHandlerInterface
{
    public function __construct(
        protected DeployInterface $deploy,
        protected Generator $generator
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!($this->deploy instanceof Memory)) {
            throw new RouteNotFoundException();
        }

        $this->generator->generate();

        $content = $this->deploy->getSet(Arr::get($request->getQueryParams(), 'id') ?? '');

        if (is_string($content)) {
            return new Response\XmlResponse($content);
        }

        return new Response\EmptyResponse(404);
    }
}
