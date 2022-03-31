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

use FoF\Sitemap\Generate\Generator;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SitemapController implements RequestHandlerInterface
{
    public function __construct(
        protected Generator $generator
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response;

//        $response->getBody()->write($this->render($request));

        return $response->withHeader('Content-Type', 'text/xml');
    }
}
