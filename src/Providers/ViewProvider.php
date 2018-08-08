<?php

namespace Flagrow\Sitemap\Providers;

use Flarum\Foundation\AbstractServiceProvider;

class ViewProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->app['view']->addNamespace('flagrow-sitemap', realpath(__DIR__ . '/../../views'));
    }
}
