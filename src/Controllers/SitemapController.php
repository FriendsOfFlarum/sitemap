<?php

namespace Flagrow\Sitemap\Controllers;

use Flagrow\Sitemap\SitemapGenerator;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\View\Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;

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
        $urlset = $this->cache->get('flagrow.sitemap') ?? $this->sitemap->getUrlSet();

        return $this->view->make('flagrow-sitemap::sitemap')
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
