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

use Carbon\Carbon;
use Flarum\Http\UrlGenerator;
use Flarum\Testing\unit\TestCase;
use FoF\Sitemap\Deploy\Memory;
use FoF\Sitemap\Deploy\StoredSet;
use Mockery as m;
use Psr\Log\NullLogger;

class MemoryDeployTest extends TestCase
{
    private Memory $deploy;

    protected function setUp(): void
    {
        parent::setUp();

        $urlGenerator = m::mock(UrlGenerator::class);
        $urlGenerator->shouldReceive('to->route')
            ->andReturn('http://example.com/sitemap-0.xml');

        $this->deploy = new Memory($urlGenerator, new NullLogger());
    }

    private function makeStream(string $content = '<urlset/>'): mixed
    {
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }

    /** @test */
    public function storeSet_accepts_stream_and_returns_StoredSet(): void
    {
        $stream = $this->makeStream('<urlset>test</urlset>');
        $result = $this->deploy->storeSet(0, $stream);
        fclose($stream);

        $this->assertInstanceOf(StoredSet::class, $result);
    }

    /** @test */
    public function storeSet_reads_stream_content_into_cache(): void
    {
        $content = '<urlset>cached content</urlset>';
        $stream  = $this->makeStream($content);

        $this->deploy->storeSet(0, $stream);
        fclose($stream);

        $this->assertEquals($content, $this->deploy->getSet(0));
    }

    /** @test */
    public function storeSet_stores_multiple_sets_independently(): void
    {
        $stream0 = $this->makeStream('<urlset>set0</urlset>');
        $stream1 = $this->makeStream('<urlset>set1</urlset>');

        $this->deploy->storeSet(0, $stream0);
        $this->deploy->storeSet(1, $stream1);

        fclose($stream0);
        fclose($stream1);

        $this->assertEquals('<urlset>set0</urlset>', $this->deploy->getSet(0));
        $this->assertEquals('<urlset>set1</urlset>', $this->deploy->getSet(1));
    }

    /** @test */
    public function storeSet_stream_position_does_not_matter_before_call(): void
    {
        // Content preceded by data written before rewind — simulates a real UrlSet stream
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, 'IGNORED_PREFIX');
        fwrite($stream, '<urlset>real content</urlset>');
        rewind($stream);
        // skip the prefix to simulate the caller rewinding after writing
        fread($stream, 14); // read past "IGNORED_PREFIX"

        // Store from mid-stream position — should capture only what remains
        $this->deploy->storeSet(0, $stream);
        fclose($stream);

        $this->assertEquals('<urlset>real content</urlset>', $this->deploy->getSet(0));
    }

    /** @test */
    public function getSet_returns_null_for_unknown_index(): void
    {
        $this->assertNull($this->deploy->getSet(99));
    }

    /** @test */
    public function storeIndex_stores_and_getIndex_retrieves(): void
    {
        $this->deploy->storeIndex('<sitemapindex/>');
        $this->assertEquals('<sitemapindex/>', $this->deploy->getIndex());
    }

    /** @test */
    public function storeSet_returns_stored_set_with_url(): void
    {
        $stream = $this->makeStream('<urlset/>');
        $result = $this->deploy->storeSet(0, $stream);
        fclose($stream);

        $this->assertNotEmpty($result->url);
        $this->assertInstanceOf(Carbon::class, $result->lastModifiedAt);
    }
}
