<?php

/*
 * This file is part of fof/sitemap.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 *  For the full copyright and license information, please view the LICENSE.md
 *  file that was distributed with this source code.
 *
 */

namespace FoF\Sitemap\Controllers;

use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\SitemapGenerator;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\View\Factory;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SitemapController implements RequestHandlerInterface
{
    protected $sitemap;
    protected $view;
    /**
     * @var Repository
     */
    private $cache;
    private $settings;

    public function __construct(SitemapGenerator $sitemap, Factory $view, Repository $cache, SettingsRepositoryInterface $settings)
    {
        $this->sitemap = $sitemap;
        $this->view = $view;
        $this->cache = $cache;
        $this->settings = $settings;
    }

    protected function render(ServerRequestInterface $request)
    {
        if ($this->settings->get('fof-sitemap.mode') === 'run') {
            $this->cache->forget('fof-sitemap');
        }

        $urlset = $this->cache->get('fof-sitemap') ?? $this->sitemap->getUrlSet();

        return $this->view->make('fof-sitemap::sitemap')
            ->with('urlset', $urlset)
            ->render();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response();

        $response->getBody()->write($this->render($request));

        return $response->withHeader('Content-Type', 'text/xml');
    }
}
