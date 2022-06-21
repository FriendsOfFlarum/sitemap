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

use FoF\Sitemap\Deploy\DeployInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SitemapController implements RequestHandlerInterface
{
    public function __construct(
        protected DeployInterface $deploy
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $index = $this->deploy->getIndex();

        if ($index instanceof Uri) {
            return new Response\RedirectResponse($index);
        }

        if (is_string($index)) {
            return new Response\XmlResponse($index);
        }

        return new Response\EmptyResponse(404);
    }
}
