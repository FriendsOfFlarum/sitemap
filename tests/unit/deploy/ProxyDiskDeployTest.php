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

namespace FoF\Sitemap\Tests\Unit\Deploy;

use Flarum\Http\UrlGenerator;
use Flarum\Testing\unit\TestCase;
use FoF\Sitemap\Deploy\ProxyDisk;
use FoF\Sitemap\Deploy\StoredSet;
use Illuminate\Contracts\Filesystem\Cloud;
use Mockery as m;
use Psr\Log\NullLogger;

class ProxyDiskDeployTest extends TestCase
{
    private function makeProxy(?Cloud $sitemapStorage = null, ?Cloud $indexStorage = null): ProxyDisk
    {
        $urlGenerator = m::mock(UrlGenerator::class);
        $urlGenerator->shouldReceive('to->route')
            ->andReturn('http://forum.example.com/sitemap-0.xml');

        return new ProxyDisk(
            $sitemapStorage ?? m::mock(Cloud::class),
            $indexStorage ?? m::mock(Cloud::class),
            $urlGenerator,
            new NullLogger()
        );
    }

    private function makeStream(string $content = '<urlset/>'): mixed
    {
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }

    /** @test */
    public function storeSet_passes_stream_to_cloud_storage(): void
    {
        $storage = m::mock(Cloud::class);
        $storage->shouldReceive('put')
            ->once()
            ->withArgs(function (string $path, $passedStream) {
                return $path === 'sitemap-0.xml' && is_resource($passedStream);
            })
            ->andReturn(true);

        $proxy = $this->makeProxy($storage);
        $stream = $this->makeStream('<urlset>s3 content</urlset>');
        $result = $proxy->storeSet(0, $stream);
        fclose($stream);

        $this->assertInstanceOf(StoredSet::class, $result);
    }

    /** @test */
    public function storeSet_returns_forum_domain_url_not_storage_url(): void
    {
        $storage = m::mock(Cloud::class);
        $storage->shouldReceive('put')->andReturn(true);

        $urlGenerator = m::mock(UrlGenerator::class);
        $urlGenerator->shouldReceive('to->route')
            ->with('fof-sitemap-set', ['id' => 2])
            ->andReturn('http://forum.example.com/sitemap-set/2');

        $proxy = new ProxyDisk($storage, m::mock(Cloud::class), $urlGenerator, new NullLogger());
        $stream = $this->makeStream();
        $result = $proxy->storeSet(2, $stream);
        fclose($stream);

        // Must return the forum domain URL (proxied), not the S3 URL
        $this->assertEquals('http://forum.example.com/sitemap-set/2', $result->url);
    }

    /** @test */
    public function storeSet_uses_correct_path_for_set_index(): void
    {
        $storage = m::mock(Cloud::class);
        $storage->shouldReceive('put')
            ->once()
            ->with('sitemap-7.xml', m::type('resource'))
            ->andReturn(true);

        $proxy = $this->makeProxy($storage);
        $stream = $this->makeStream();
        $proxy->storeSet(7, $stream);
        fclose($stream);
    }
}
