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
use FoF\Sitemap\Deploy\Disk;
use FoF\Sitemap\Deploy\StoredSet;
use Illuminate\Contracts\Filesystem\Cloud;
use Mockery as m;
use Psr\Log\NullLogger;

class DiskDeployTest extends TestCase
{
    private function makeDisk(?Cloud $sitemapStorage = null, ?Cloud $indexStorage = null): Disk
    {
        $urlGenerator = m::mock(UrlGenerator::class);
        $urlGenerator->shouldReceive('to->route')
            ->andReturn('http://example.com/sitemap-0.xml');

        return new Disk(
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
    public function storeSet_passes_stream_resource_to_filesystem(): void
    {
        $content = '<urlset>disk content</urlset>';
        $stream = $this->makeStream($content);

        $storage = m::mock(Cloud::class);
        // Flysystem Cloud::put() accepts a string or resource; verify it receives a resource
        $storage->shouldReceive('put')
            ->once()
            ->withArgs(function (string $path, $passedStream) {
                return $path === 'sitemap-0.xml' && is_resource($passedStream);
            })
            ->andReturn(true);

        $storage->shouldReceive('url')->andReturn('http://example.com/sitemaps/sitemap-0.xml');

        $disk = $this->makeDisk($storage, m::mock(Cloud::class));
        $result = $disk->storeSet(0, $stream);
        fclose($stream);

        $this->assertInstanceOf(StoredSet::class, $result);
    }

    /** @test */
    public function storeSet_uses_correct_path_for_index(): void
    {
        $storage = m::mock(Cloud::class);
        $storage->shouldReceive('put')
            ->once()
            ->with('sitemap-3.xml', m::type('resource'))
            ->andReturn(true);
        $storage->shouldReceive('url')->andReturn('http://example.com/sitemaps/sitemap-3.xml');

        $disk = $this->makeDisk($storage, m::mock(Cloud::class));
        $stream = $this->makeStream('<urlset/>');
        $disk->storeSet(3, $stream);
        fclose($stream);
    }

    /** @test */
    public function storeSet_returns_StoredSet_with_forum_route_url(): void
    {
        $storage = m::mock(Cloud::class);
        $storage->shouldReceive('put')->andReturn(true);
        $storage->shouldReceive('url')->andReturn('http://s3.example.com/sitemaps/sitemap-0.xml');

        $urlGenerator = m::mock(UrlGenerator::class);
        $urlGenerator->shouldReceive('to->route')
            ->with('fof-sitemap-set', ['id' => 0])
            ->andReturn('http://forum.example.com/sitemap-set/0');

        $disk = new Disk($storage, m::mock(Cloud::class), $urlGenerator, new NullLogger());
        $stream = $this->makeStream();
        $result = $disk->storeSet(0, $stream);
        fclose($stream);

        $this->assertEquals('http://forum.example.com/sitemap-set/0', $result->url);
    }

    /** @test */
    public function storeSet_exception_propagates(): void
    {
        $this->expectException(\RuntimeException::class);

        $storage = m::mock(Cloud::class);
        $storage->shouldReceive('put')->andThrow(new \RuntimeException('Disk full'));
        $storage->shouldReceive('url')->andReturn('http://example.com/sitemaps/sitemap-0.xml');

        $disk = $this->makeDisk($storage, m::mock(Cloud::class));
        $stream = $this->makeStream();

        try {
            $disk->storeSet(0, $stream);
        } finally {
            fclose($stream);
        }
    }
}
