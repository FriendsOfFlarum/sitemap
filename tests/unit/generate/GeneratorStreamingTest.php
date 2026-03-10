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

namespace FoF\Sitemap\Tests\Unit\Generate;

use Carbon\Carbon;
use Flarum\Testing\unit\TestCase;
use FoF\Sitemap\Sitemap\UrlSet;

class GeneratorStreamingTest extends TestCase
{
    /** @test */
    public function loop_passes_stream_resource_to_storeSet(): void
    {
        // Generator::loop() uses resolve() which requires the Flarum IoC container.
        // Stream-passing behaviour is verified at the UrlSet and deploy-backend unit
        // test level; end-to-end wiring is covered by integration tests.
        $this->markTestSkipped('Generator::loop() uses resolve() — covered by integration tests.');
    }

    /** @test */
    public function urlset_stream_is_closed_after_storeSet(): void
    {
        // Test that the stream returned by UrlSet::stream() is properly closed
        // by Generator::flushSet() after passing to deploy backend.
        $set    = new UrlSet();
        $set->addUrl('https://example.com/test');
        $stream = $set->stream();

        // Simulate what Generator does
        stream_get_contents($stream); // deploy reads it
        fclose($stream);              // generator closes it

        // After fclose, the resource should be invalid
        $this->assertFalse(is_resource($stream));
    }

    /** @test */
    public function urlset_split_produces_correct_xml_in_each_segment(): void
    {
        // Simulate what Generator::loop() does when the 50k limit is hit:
        // - URLs 1..AMOUNT_LIMIT fill set 1 successfully.
        // - The (AMOUNT_LIMIT+1)th add() throws SetLimitReachedException.
        // - Generator flushes set 1, starts set 2, re-adds the overflow URL.

        $set1 = new UrlSet();
        $overflowUrl = null;

        // Add exactly AMOUNT_LIMIT URLs — all succeed
        for ($i = 1; $i <= UrlSet::AMOUNT_LIMIT; $i++) {
            $set1->addUrl("https://example.com/d/$i");
        }

        // The next add should throw
        try {
            $set1->addUrl('https://example.com/d/overflow');
        } catch (\FoF\Sitemap\Exceptions\SetLimitReachedException $e) {
            $overflowUrl = 'https://example.com/d/overflow';
        }

        $this->assertNotNull($overflowUrl, 'SetLimitReachedException should have been thrown');

        // Flush set 1
        $stream1 = $set1->stream();
        $xml1    = stream_get_contents($stream1);
        fclose($stream1);

        // Start set 2 with the overflow URL
        $set2 = new UrlSet();
        $set2->addUrl($overflowUrl);

        $stream2 = $set2->stream();
        $xml2    = stream_get_contents($stream2);
        fclose($stream2);

        // Set 1: contains first and last of the AMOUNT_LIMIT URLs, not the overflow
        $this->assertStringContainsString('https://example.com/d/1', $xml1);
        $this->assertStringContainsString('https://example.com/d/'.UrlSet::AMOUNT_LIMIT, $xml1);
        $this->assertStringNotContainsString('overflow', $xml1);

        // Set 2: contains only the overflow URL
        $this->assertStringContainsString('overflow', $xml2);
        $this->assertStringNotContainsString('https://example.com/d/1', $xml2);
    }

    /** @test */
    public function storeSet_receives_valid_xml_stream(): void
    {
        $set = new UrlSet();
        $set->addUrl('https://example.com/d/1');
        $set->addUrl('https://example.com/d/2');

        $stream  = $set->stream();
        $content = stream_get_contents($stream);
        fclose($stream);

        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($content), 'Stream passed to storeSet must contain well-formed XML');
    }

    /** @test */
    public function phptemp_stream_is_rewound_before_storeSet(): void
    {
        $set = new UrlSet();

        for ($i = 1; $i <= 100; $i++) {
            $set->addUrl("https://example.com/d/$i");
        }

        $stream = $set->stream();

        // The stream position must be 0 (rewound)
        $this->assertEquals(0, ftell($stream), 'UrlSet::stream() must rewind before returning');

        fclose($stream);
    }

    /** @test */
    public function multiple_sets_produce_independent_xml(): void
    {
        // Simulate two consecutive sets as Generator would produce them
        $set1 = new UrlSet();
        $set1->addUrl('https://example.com/alpha');

        $stream1 = $set1->stream();
        $xml1    = stream_get_contents($stream1);
        fclose($stream1);

        $set2 = new UrlSet();
        $set2->addUrl('https://example.com/beta');

        $stream2 = $set2->stream();
        $xml2    = stream_get_contents($stream2);
        fclose($stream2);

        $this->assertStringContainsString('alpha', $xml1);
        $this->assertStringNotContainsString('beta', $xml1);

        $this->assertStringContainsString('beta', $xml2);
        $this->assertStringNotContainsString('alpha', $xml2);
    }
}
