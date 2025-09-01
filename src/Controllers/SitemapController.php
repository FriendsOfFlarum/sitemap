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
use GuzzleHttp\Client;
use FoF\Sitemap\Deploy\Memory;
use FoF\Sitemap\Generate\Generator;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class SitemapController implements RequestHandlerInterface
{
    public function __construct(
        protected DeployInterface $deploy,
        protected SettingsRepositoryInterface $settings,
        protected Generator $generator,
        protected LoggerInterface $logger
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Get route parameters from the request attributes
        $routeParams = $request->getAttribute('routeParameters', []);
        /** @var string|null $id */
        $id = Arr::get($routeParams, 'id');

        $this->logger->debug('[FoF Sitemap] Route parameters: '.json_encode($routeParams));
        $this->logger->debug('[FoF Sitemap] Extracted ID: '.($id ?? 'null'));

        if ($id !== null) {
            // Individual sitemap request
            $this->logger->debug("[FoF Sitemap] Handling individual sitemap request for set: $id");

            if ($this->deploy instanceof Memory) {
                $this->logger->debug('[FoF Sitemap] Memory deployment: Generating sitemap on-the-fly');
                $this->generator->generate();
            }

            $content = $this->deploy->getSet($id);
        } else {
            // Index request
            $this->logger->debug('[FoF Sitemap] Handling sitemap index request');

            if ($this->deploy instanceof Memory) {
                $this->logger->debug('[FoF Sitemap] Memory deployment: Generating sitemap on-the-fly');
                $this->generator->generate();
            }

            $content = $this->deploy->getIndex();
        }

        if (is_string($content) && !empty($content)) {
            $this->logger->debug('[FoF Sitemap] Successfully serving sitemap content');

            return new Response\XmlResponse($content);
        }

        $this->logger->debug('[FoF Sitemap] No sitemap content found, returning 404');

        return new Response\EmptyResponse(404);
    }
}
