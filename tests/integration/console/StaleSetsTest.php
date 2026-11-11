<?php

/*
 * This file is part of fof/sitemap.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 */

namespace FoF\Sitemap\Tests\integration\console;

use Flarum\Testing\integration\TestCase;
use FoF\Sitemap\Deploy\Memory;
use FoF\Sitemap\Generate\Generator;
use Flarum\Settings\SettingsRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;

class StaleSetsTest extends TestCase
{
    #[Test]
    public function concurrent_disk_build_skips_without_writing_sets(): void
    {
        $this->extension('fof-sitemap');
        $this->setting('fof-sitemap.mode', 'multi-file');
        $container = $this->app()->getContainer();
        $lock = $container->make('cache.store')->getStore()->lock('fof-sitemap.build');
        $this->assertTrue($lock->get());
        try {
            $storage = $this->storage();
            $deploy = $container->make(\FoF\Sitemap\Deploy\Disk::class, ['sitemapStorage' => $storage, 'indexStorage' => $storage]);
            $result = (new Generator($deploy, [\FoF\Sitemap\Resources\StaticUrls::class], $container->make(SettingsRepositoryInterface::class)))->generate();
            $this->assertNull($result);
            $this->assertSame([], $storage->files());
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function published_build_time_is_recorded_even_when_cleanup_fails(): void
    {
        $this->extension('fof-sitemap');
        $this->setting('fof-sitemap.last_build_time', '1');
        $container = $this->app()->getContainer();
        $deploy = new class($container->make(\Flarum\Http\UrlGenerator::class), $container->make(\Psr\Log\LoggerInterface::class)) extends Memory {
            public function removeStaleSets(int $setCount): void
            {
                throw new \RuntimeException('Cleanup failed');
            }
        };
        $settings = $container->make(SettingsRepositoryInterface::class);
        try {
            (new Generator($deploy, [], $settings))->generate();
            $this->fail('Cleanup failure must be reported');
        } catch (\RuntimeException $error) {
            $this->assertSame('Cleanup failed', $error->getMessage());
        }
        $this->assertStringContainsString('sitemapindex', $deploy->getIndex());
        $this->assertGreaterThan(1, $settings->get('fof-sitemap.last_build_time'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('backends')]
    #[Test]
    public function smaller_rebuild_keeps_current_set_and_publishes_before_pruning(string $backend): void
    {
        $this->extension('fof-sitemap');
        $container = $this->app()->getContainer();
        $storage = $this->storage();
        $deploy = $container->make($backend, ['sitemapStorage' => $storage, 'indexStorage' => $storage]);
        foreach ([0, 1, 2] as $id) {
            $deploy->storeSet($id, 'old URLs');
        }
        $deploy->storeIndex('old index');
        $storage->beforeDelete = function () use ($deploy) {
            $this->assertStringContainsString('sitemapindex', $deploy->getIndex());
            $this->assertStringContainsString('urlset', $deploy->getSet(0));
        };

        (new Generator($deploy, [\FoF\Sitemap\Resources\StaticUrls::class], $container->make(SettingsRepositoryInterface::class)))->generate();

        $this->assertStringContainsString('urlset', $deploy->getSet(0));
        $this->assertStringContainsString('sitemap-0.xml', $deploy->getIndex());
        $this->assertNull($deploy->getSet(1));
        $this->assertNull($deploy->getSet(2));
    }

    public static function diskBackends(): array
    {
        return [[\FoF\Sitemap\Deploy\Disk::class], [\FoF\Sitemap\Deploy\ProxyDisk::class]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('diskBackends')]
    #[Test]
    public function failed_index_publication_keeps_old_sets_and_build_time(string $backend): void
    {
        $this->extension('fof-sitemap');
        $this->setting('fof-sitemap.last_build_time', '1');
        $container = $this->app()->getContainer();
        $storage = $this->storage();
        $deploy = $container->make($backend, ['sitemapStorage' => $storage, 'indexStorage' => $storage]);
        $deploy->storeSet(0, 'old set zero');
        $deploy->storeSet(1, 'old set one');
        $deploy->storeIndex('old index');
        $storage->failIndex = true;
        $settings = $container->make(SettingsRepositoryInterface::class);
        try {
            (new Generator($deploy, [\FoF\Sitemap\Resources\StaticUrls::class], $settings))->generate();
            $this->fail('Index publication failure must be reported');
        } catch (\RuntimeException $error) {
            $this->assertSame('Unable to publish sitemap index', $error->getMessage());
        }
        $this->assertSame('old index', $deploy->getIndex());
        $this->assertStringContainsString('urlset', $deploy->getSet(0));
        $this->assertSame('old set one', $deploy->getSet(1));
        $this->assertSame('1', $settings->get('fof-sitemap.last_build_time'));
        $storage->failIndex = false;
        // A failed publication releases the build lock too.
        (new Generator($deploy, [], $settings))->generate();
        $this->assertNull($deploy->getSet(1));
    }

    protected function storage(): \Illuminate\Filesystem\FilesystemAdapter
    {
        $adapter = new class extends \League\Flysystem\InMemory\InMemoryFilesystemAdapter {
            public function getUrl(string $path): string { return 'https://example.com/'.$path; }
        };
        return new class(new \League\Flysystem\Filesystem($adapter), $adapter) extends \Illuminate\Filesystem\FilesystemAdapter {
            public bool $failIndex = false;
            public bool $failSet = false;
            public ?\Closure $beforeDelete = null;

            public function put($path, $contents, $options = [])
            {
                return ($this->failIndex && $path === 'sitemap.xml') || ($this->failSet && $path === 'sitemap-0.xml') ? false : parent::put($path, $contents, $options);
            }

            public function delete($paths)
            {
                if ($this->beforeDelete) {
                    ($this->beforeDelete)();
                }
                return parent::delete($paths);
            }
        };
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('diskBackends')]
    #[Test]
    public function lost_lease_aborts_before_set_writes_or_publication(string $backend): void
    {
        $this->extension('fof-sitemap');
        $container = $this->app()->getContainer();
        $storage = $this->storage();
        $deploy = $container->make($backend, ['sitemapStorage' => $storage, 'indexStorage' => $storage]);
        $deploy->storeSet(0, 'old set');
        $deploy->storeSet(1, 'old surplus set');
        $deploy->storeIndex('old index');
        try {
            (new Generator($deploy, [LostLeaseResource::class], $container->make(SettingsRepositoryInterface::class)))->generate();
            $this->fail('A build that lost its lease must abort');
        } catch (\RuntimeException $error) {
            $this->assertSame('Sitemap build lease was lost', $error->getMessage());
        }
        $this->assertSame('old index', $deploy->getIndex());
        $this->assertSame('old set', $deploy->getSet(0));
        $this->assertSame('old surplus set', $deploy->getSet(1));
    }

    #[Test]
    public function expired_build_lease_allows_a_later_rebuild(): void
    {
        $this->extension('fof-sitemap');
        $container = $this->app()->getContainer();
        $storage = $this->storage();
        $deploy = $container->make(\FoF\Sitemap\Deploy\Disk::class, ['sitemapStorage' => $storage, 'indexStorage' => $storage]);
        try {
            (new Generator($deploy, [ExpiredLeaseResource::class], $container->make(SettingsRepositoryInterface::class)))->generate();
            $this->fail('An expired build must abort');
        } catch (\RuntimeException $error) {
            $this->assertSame('Sitemap build lease was lost', $error->getMessage());
        } finally {
            \Illuminate\Support\Carbon::setTestNow();
        }
        (new Generator($deploy, [\FoF\Sitemap\Resources\StaticUrls::class], $container->make(SettingsRepositoryInterface::class)))->generate();
        $this->assertStringContainsString('sitemapindex', $deploy->getIndex());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('diskBackends')]
    #[Test]
    public function failed_set_write_never_publishes_or_prunes(string $backend): void
    {
        $this->extension('fof-sitemap');
        $container = $this->app()->getContainer();
        $storage = $this->storage();
        $deploy = $container->make($backend, ['sitemapStorage' => $storage, 'indexStorage' => $storage]);
        $deploy->storeSet(1, 'old surplus');
        $deploy->storeIndex('old index');
        $storage->failSet = true;
        try {
            (new Generator($deploy, [\FoF\Sitemap\Resources\StaticUrls::class], $container->make(SettingsRepositoryInterface::class)))->generate();
            $this->fail('Set write failure must be reported');
        } catch (\RuntimeException $error) {
            $this->assertSame('Unable to store sitemap set: sitemap-0.xml', $error->getMessage());
        }
        $this->assertSame('old index', $deploy->getIndex());
        $this->assertSame('old surplus', $deploy->getSet(1));
    }

    #[Test]
    public function switching_to_runtime_does_not_delete_an_active_builds_files(): void
    {
        $this->extension('fof-sitemap');
        $this->setting('fof-sitemap.mode', 'multi-file');
        $container = $this->app()->getContainer();
        $deploy = $container->make(\FoF\Sitemap\Deploy\DeployInterface::class);
        $deploy->storeSet(0, 'building set');
        $deploy->storeIndex('published index');
        $lock = $container->make('cache.store')->getStore()->lock('fof-sitemap.build', 600);
        $this->assertTrue($lock->get());
        $settings = ['fof-sitemap.mode' => 'run'];
        try {
            $container->make(\FoF\Sitemap\Listeners\SettingsListener::class)->whenSaving(new \Flarum\Settings\Event\Saving($settings));
            $this->assertSame('building set', $deploy->getSet(0));
            $this->assertSame('published index', $deploy->getIndex());
        } finally {
            $lock->release();
        }
        $container->make(\FoF\Sitemap\Listeners\SettingsListener::class)->whenSaving(new \Flarum\Settings\Event\Saving($settings));
        $this->assertNull($deploy->getSet(0));
    }

    public static function backends(): array
    {
        return [[Memory::class], [\FoF\Sitemap\Deploy\Disk::class], [\FoF\Sitemap\Deploy\ProxyDisk::class]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('backends')]
    #[Test]
    public function empty_rebuild_removes_old_sets_after_publishing_index(string $backend): void
    {
        $this->extension('fof-sitemap');
        $container = $this->app()->getContainer();
        $adapter = new class extends \League\Flysystem\InMemory\InMemoryFilesystemAdapter {
            public function getUrl(string $path): string
            {
                return 'https://example.com/'.$path;
            }
        };
        $storage = new \Illuminate\Filesystem\FilesystemAdapter(new \League\Flysystem\Filesystem($adapter), $adapter, ['url' => 'https://example.com']);
        $storage->put('unrelated.xml', 'keep');
        $deploy = $container->make($backend, ['sitemapStorage' => $storage, 'indexStorage' => $storage]);
        $deploy->storeSet(0, 'excluded URLs');
        $deploy->storeSet(1, 'more excluded URLs');
        $deploy->storeIndex('old index');

        (new Generator($deploy, [], $container->make(SettingsRepositoryInterface::class)))->generate();

        $this->assertStringContainsString('sitemapindex', $deploy->getIndex());
        $this->assertNull($deploy->getSet(0));
        $this->assertNull($deploy->getSet(1));
        $this->assertSame('keep', $storage->get('unrelated.xml'));
        $this->assertFalse($storage->exists('sitemap-0.xml'));
    }
}

class LostLeaseResource extends \FoF\Sitemap\Resources\StaticUrls
{
    public function url($routeName): string
    {
        resolve('cache.store')->getStore()->lock('fof-sitemap.build')->forceRelease();
        return parent::url($routeName);
    }
}

class ExpiredLeaseResource extends \FoF\Sitemap\Resources\StaticUrls
{
    public function url($routeName): string
    {
        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::now()->addMinutes(11));
        return parent::url($routeName);
    }
}
