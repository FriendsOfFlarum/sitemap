<?php

namespace FoF\Sitemap\Providers;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
use Flarum\Foundation\Paths;
use FoF\Sitemap\Deploy\Disk;
use FoF\Sitemap\Deploy\DeployInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class DeployProvider extends AbstractServiceProvider
{
    public function register()
    {
        // Create a sane default for storing sitemap files.
        $this->container->singleton(DeployInterface::class, function (Container $container) {
            $storage = $this->localStorage($container);

            return new Disk(
                $storage,
                $storage
            );
        });
    }

    public function localStorage(Container $container): Cloud
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
}