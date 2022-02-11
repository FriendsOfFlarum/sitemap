<?php

namespace FoF\Sitemap\Providers;

use Flarum\Filesystem\FilesystemManager;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Storage\CacheableFilesystemDriver;
use FoF\Sitemap\Storage\StorageInterface;
use Illuminate\Cache\NullStore;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;

class StorageProvider extends AbstractServiceProvider
{
    public function register()
    {
        // Set up a temporary disk used for generating the sitemaps.
        $this->container->singleton('fof.sitemap.tmp-disk', function (Container $container) {
            /** @var Paths $paths */
            $paths = $container->make(Paths::class);

            return new FilesystemAdapter(
                new Filesystem(new Local($paths->storage . '/fof-sitemaps/'))
            );
        });

        // Set a sane default for the storage facility for storing sitemaps.
        $this->container->singleton('fof.sitemap.storage', function (Container $container) {
            /** @var SettingsRepositoryInterface $settings */
            $settings = $container->make(SettingsRepositoryInterface::class);

            /** @var FilesystemManager $filesystemManager */
            $filesystemManager = $container->make('filesystem');

            $mode = $settings->get('fof-sitemap.mode', 'run');

            $fs = new NullAdapter;
            $cache = new NullStore;

            if ($mode === 'cache') {
                /** @var Repository $cache */
                $cache = resolve(Repository::class);
            }
            if ($mode === 'cache-disk' || $mode === 'multi-file') {
                $fs = $filesystemManager->disk('flarum-assets');
            }

            return new CacheableFilesystemDriver(
                $this->wrapFs($fs), $cache
            );
        });

        // Registers temporary disk handler on the storage disk handler.
        $this->container->afterResolving('fof.sitemap.storage', function (StorageInterface $storage) {
            forward_static_call([$storage, 'setTemporaryFilesystem'], resolve('fof.sitemap.tmp-disk'));
        });
    }

    protected function wrapFs($filesystem)
    {
        if ($filesystem instanceof AbstractAdapter) {
            $filesystem = new Filesystem($filesystem);
        }

        if ($filesystem instanceof Filesystem) {
            return new FilesystemAdapter($filesystem);
        }

        return $filesystem;
    }
}
