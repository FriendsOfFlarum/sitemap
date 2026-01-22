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

use FoF\Sitemap\Generate\RobotsGenerator;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller for serving robots.txt files.
 *
 * This controller generates and serves a standards-compliant robots.txt
 * file using the registered robots.txt entries. The content is generated
 * dynamically on each request.
 */
class RobotsController implements RequestHandlerInterface
{
    /**
     * @param RobotsGenerator $generator The robots.txt generator instance
     * @param LoggerInterface $logger The logger instance
     */
    public function __construct(
        protected RobotsGenerator $generator,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Handle the robots.txt request.
     *
     * Generates the robots.txt content and returns it with the appropriate
     * content type header.
     *
     * @param ServerRequestInterface $request The HTTP request
     *
     * @return ResponseInterface The robots.txt response
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $serverParams = $request->getServerParams();
        $ip = $serverParams['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $request->getHeaderLine('User-Agent') ?: 'unknown';

        $this->logger->debug("[FoF Sitemap] Received robots.txt request from IP: {$ip}, User-Agent: {$userAgent}");

        $content = $this->generator->generate();

        $this->logger->debug('[FoF Sitemap] Successfully serving robots.txt content');

        return new TextResponse($content, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
