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

use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Deploy\DeployInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SitemapController implements RequestHandlerInterface
{
    public function __construct(
        protected DeployInterface $deploy,
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $index = $this->deploy->getIndex();

        if ($index instanceof Uri) {
            // We fetch the contents of the file here, as we must return a non-redirect reposnse.
            // This is required as when Flarum is configured to use S3 or other CDN, the actual file
            // lives off of the Flarum domain, and this index must be hosted under the Flarum domain.
            $index = $this->fetchContentsFromUri($index);
        }

        if (is_string($index)) {
            return new Response\XmlResponse($index);
        }

        return new Response\EmptyResponse(404);
    }

    protected function fetchContentsFromUri(Uri $uri): string
    {
        $client = new \GuzzleHttp\Client();

        return $client->get($uri)->getBody()->getContents();
    }
}
