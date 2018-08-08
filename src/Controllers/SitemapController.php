<?php

namespace Flagrow\Sitemap\Controllers;

use Flagrow\Sitemap\SitemapGenerator;
use Flarum\Http\Controller\ControllerInterface;
use Illuminate\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response;

class SitemapController implements ControllerInterface
{
    protected $sitemap;
    protected $view;

    public function __construct(SitemapGenerator $sitemap, Factory $view)
    {
        $this->sitemap = $sitemap;
        $this->view = $view;
    }

    protected function render(Request $request)
    {
        return $this->view->make('flagrow-sitemap::sitemap')
            ->with('urlset', $this->sitemap->getUrlSet())
            ->render();
    }

    public function handle(Request $request)
    {
        $response = new Response();

        $response->getBody()->write($this->render($request));

        return $response->withHeader('Content-Type', 'text/xml');
    }
}
