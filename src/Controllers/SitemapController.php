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
use FoF\Sitemap\Deploy\Memory;
use FoF\Sitemap\Generate\Generator;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SitemapController implements RequestHandlerInterface
{
    public function __construct(
        protected DeployInterface $deploy,
        protected SettingsRepositoryInterface $settings,
        protected Generator $generator
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $logger = resolve('log');
        
        // Get route parameters from the request attributes
        $routeParams = $request->getAttribute('routeParameters', []);
        $id = $routeParams['id'] ?? null;

        $logger->debug("[FoF Sitemap] Route parameters: " . json_encode($routeParams));
        $logger->debug("[FoF Sitemap] Extracted ID: " . ($id ?? 'null'));

        if ($id !== null) {
            // Individual sitemap request
            $logger->debug("[FoF Sitemap] Handling individual sitemap request for set: $id");
            
            if ($this->deploy instanceof Memory) {
                $logger->debug('[FoF Sitemap] Memory deployment: Generating sitemap on-the-fly');
                $this->generator->generate();
            }
            
            $content = $this->deploy->getSet($id);
        } else {
            // Index request
            $logger->debug('[FoF Sitemap] Handling sitemap index request');
            
            if ($this->deploy instanceof Memory) {
                $logger->debug('[FoF Sitemap] Memory deployment: Generating sitemap on-the-fly');
                $this->generator->generate();
            }
            
            $content = $this->deploy->getIndex();
        }

        if (is_string($content) && !empty($content)) {
            $logger->debug('[FoF Sitemap] Successfully serving sitemap content');
            return new Response\XmlResponse($content);
        }

        $logger->debug('[FoF Sitemap] No sitemap content found, returning 404');
        return new Response\EmptyResponse(404);
    }
}
