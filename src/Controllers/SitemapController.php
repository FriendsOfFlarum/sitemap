<?php

namespace FoF\Sitemap\Controllers;

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

    public function __construct(SitemapGenerator $sitemap, Factory $view, Repository $cache)
    {
        $this->sitemap = $sitemap;
        $this->view = $view;
        $this->cache = $cache;
    }

    protected function render(ServerRequestInterface $request)
    {
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
