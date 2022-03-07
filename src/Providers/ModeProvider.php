<?php

namespace FoF\Sitemap\Providers;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
use Flarum\Foundation\Paths;
use FoF\Sitemap\Modes\Disk;
use FoF\Sitemap\Modes\ModeInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class ModeProvider extends AbstractServiceProvider
{
    public function register()
    {
        // Create a sane default for storing sitemap files.
        $this->container->singleton(ModeInterface::class, function (Container $container) {
            return new Disk(
                $this->localSitemapStorage($container),
                $this->localIndexStorage($container)
            );
        });
    }

    public function localSitemapStorage(Container $container): Cloud
    {
        /** @var Paths $paths */
        $paths = $container->make(Paths::class);

        /** @var Config $config */
        $config = $container->make(Config::class);

        $local = new Local($paths->public . '/sitemaps/');

        return new FilesystemAdapter(
            new Filesystem($local, [
                // We set this in options, because the wrapper uses this to resolve its url() method.
                'url' => $config->url() . '/sitemaps/'
            ])
        );
    }

    public function localIndexStorage(Container $container): Cloud
    {
        /** @var Paths $paths */
        $paths = $container->make(Paths::class);

        $local = new Local($paths->storage . '/sitemaps/');

        return new FilesystemAdapter(
            new Filesystem($local)
        );
    }
}
