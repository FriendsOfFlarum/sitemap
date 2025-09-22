<?php

namespace FoF\Sitemap\Controllers;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RobotsController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = '';

        // return new Response(
        //     $body,
        //     200,
        //     ['Content-Type' => 'text/plain; charset=utf-8'],
        // );

        return new EmptyResponse(200);
    }
}
