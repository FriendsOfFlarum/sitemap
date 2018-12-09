<?php

namespace Flagrow\Sitemap\Controllers;

use Flagrow\Sitemap\SitemapGenerator;
use Illuminate\View\Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;

class SitemapController implements RequestHandlerInterface
{
    protected $sitemap;
    protected $view;

    public function __construct(SitemapGenerator $sitemap, Factory $view)
    {
        $this->sitemap = $sitemap;
        $this->view = $view;
    }

    protected function render(ServerRequestInterface $request)
    {
        return $this->view->make('flagrow-sitemap::sitemap')
            ->with('urlset', $this->sitemap->getUrlSet())
            ->render();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response();

        $response->getBody()->write($this->render($request));

        return $response->withHeader('Content-Type', 'text/xml');
    }
}
