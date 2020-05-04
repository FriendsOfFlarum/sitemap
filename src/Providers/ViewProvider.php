<?php

namespace FoF\Sitemap\Providers;

use Flarum\Foundation\AbstractServiceProvider;

class ViewProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->app['view']->addNamespace('fof-sitemap', realpath(__DIR__ . '/../../views'));
    }
}
