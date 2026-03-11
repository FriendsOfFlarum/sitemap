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

namespace FoF\Sitemap\Tests\Unit\Sitemap;

use Carbon\Carbon;
use Flarum\Testing\unit\TestCase;
use FoF\Sitemap\Exceptions\SetLimitReachedException;
use FoF\Sitemap\Sitemap\Url;
use FoF\Sitemap\Sitemap\UrlSet;

class UrlSetStreamingTest extends TestCase
{
    private function makeUrl(string $loc = 'https://example.com/d/1'): Url
    {
        return new Url($loc, Carbon::parse('2024-01-01'), 'weekly', 0.9);
    }

    /** @test */
    public function stream_returns_valid_resource(): void
    {
        $set = new UrlSet();
        $set->add($this->makeUrl());

        $stream = $set->stream();

        $this->assertIsResource($stream);
        fclose($stream);
    }

    /** @test */
    public function stream_is_rewound_to_start(): void
    {
        $set = new UrlSet();
        $set->add($this->makeUrl());

        $stream = $set->stream();

        $this->assertEquals(0, ftell($stream));
        fclose($stream);
    }

    /** @test */
    public function stream_contains_valid_xml_document(): void
    {
        $set = new UrlSet();
        $set->add($this->makeUrl('https://example.com/d/1'));

        $stream = $set->stream();
        $xml = stream_get_contents($stream);
        fclose($stream);

        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'UrlSet stream must produce well-formed XML');
    }

    /** @test */
    public function stream_xml_contains_urlset_root(): void
    {
        $set = new UrlSet();
        $set->add($this->makeUrl('https://example.com/d/42'));

        $stream = $set->stream();
        $xml = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString('</urlset>', $xml);
    }

    /** @test */
    public function stream_xml_contains_added_url(): void
    {
        $set = new UrlSet();
        $set->add($this->makeUrl('https://example.com/d/99'));

        $stream = $set->stream();
        $xml = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringContainsString('https://example.com/d/99', $xml);
        $this->assertStringContainsString('<loc>', $xml);
    }

    /** @test */
    public function count_reflects_urls_added(): void
    {
        $set = new UrlSet();
        $this->assertEquals(0, $set->count());

        $set->add($this->makeUrl('https://example.com/1'));
        $this->assertEquals(1, $set->count());

        $set->add($this->makeUrl('https://example.com/2'));
        $this->assertEquals(2, $set->count());
    }

    /** @test */
    public function add_throws_when_limit_reached(): void
    {
        $this->expectException(SetLimitReachedException::class);

        $set = new UrlSet();

        // Fill to the limit
        for ($i = 0; $i < UrlSet::AMOUNT_LIMIT; $i++) {
            $set->add($this->makeUrl("https://example.com/d/$i"));
        }

        // This should throw
        $set->add($this->makeUrl('https://example.com/overflow'));
    }

    /** @test */
    public function changefreq_included_when_enabled(): void
    {
        $set = new UrlSet(includeChangefreq: true, includePriority: false);
        $set->add(new Url('https://example.com/', null, 'weekly', null));

        $stream = $set->stream();
        $xml = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringContainsString('<changefreq>weekly</changefreq>', $xml);
    }

    /** @test */
    public function changefreq_omitted_when_disabled(): void
    {
        $set = new UrlSet(includeChangefreq: false, includePriority: false);
        $set->add(new Url('https://example.com/', null, 'weekly', null));

        $stream = $set->stream();
        $xml = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringNotContainsString('<changefreq>', $xml);
    }

    /** @test */
    public function priority_included_when_enabled(): void
    {
        $set = new UrlSet(includeChangefreq: false, includePriority: true);
        $set->add(new Url('https://example.com/', null, null, 0.8));

        $stream = $set->stream();
        $xml = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringContainsString('<priority>0.8</priority>', $xml);
    }

    /** @test */
    public function priority_omitted_when_disabled(): void
    {
        $set = new UrlSet(includeChangefreq: false, includePriority: false);
        $set->add(new Url('https://example.com/', null, null, 0.8));

        $stream = $set->stream();
        $xml = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringNotContainsString('<priority>', $xml);
    }

    /** @test */
    public function lastmod_is_written_in_w3c_format(): void
    {
        $set = new UrlSet();
        $set->add(new Url('https://example.com/', Carbon::parse('2024-06-15 12:00:00', 'UTC')));

        $stream = $set->stream();
        $xml = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringContainsString('<lastmod>', $xml);
        $this->assertStringContainsString('2024-06-15', $xml);
    }

    /** @test */
    public function multiple_urls_all_appear_in_stream(): void
    {
        $set = new UrlSet();

        for ($i = 1; $i <= 5; $i++) {
            $set->add($this->makeUrl("https://example.com/d/$i"));
        }

        $stream = $set->stream();
        $xml = stream_get_contents($stream);
        fclose($stream);

        for ($i = 1; $i <= 5; $i++) {
            $this->assertStringContainsString("https://example.com/d/$i", $xml);
        }
    }

    /** @test */
    public function xml_namespace_is_present(): void
    {
        $set = new UrlSet();
        $set->add($this->makeUrl());

        $stream = $set->stream();
        $xml = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringContainsString('http://www.sitemaps.org/schemas/sitemap/0.9', $xml);
    }

    /** @test */
    public function addUrl_convenience_method_works(): void
    {
        $set = new UrlSet();
        $set->addUrl('https://example.com/page', Carbon::parse('2024-01-01'), 'monthly', 0.5);

        $this->assertEquals(1, $set->count());

        $stream = $set->stream();
        $xml = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringContainsString('https://example.com/page', $xml);
    }

    /** @test */
    public function empty_urlset_stream_is_valid_xml(): void
    {
        $set = new UrlSet();
        $stream = $set->stream();
        $xml = stream_get_contents($stream);
        fclose($stream);

        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'Empty UrlSet stream must produce well-formed XML');
        // An empty urlset may render as <urlset .../> (self-closing) — both forms are valid XML.
        $this->assertStringContainsString('<urlset', $xml);
    }

    /** @test */
    public function stream_xml_validates_against_sitemap_schema(): void
    {
        $set = new UrlSet();
        $set->add($this->makeUrl('https://example.com/d/1'));
        $set->add($this->makeUrl('https://example.com/d/2'));

        $stream = $set->stream();
        $xml = stream_get_contents($stream);
        fclose($stream);

        $schemaPath = __DIR__.'/../../fixtures/sitemap.xsd';

        if (!file_exists($schemaPath)) {
            $this->markTestSkipped('Sitemap XSD fixture not found at '.$schemaPath);
        }

        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        libxml_use_internal_errors(true);
        $isValid = $doc->schemaValidate($schemaPath);
        $errors = array_map(fn ($e) => trim($e->message), libxml_get_errors());
        libxml_clear_errors();

        $this->assertTrue($isValid, 'UrlSet XML must validate against sitemap schema: '.implode(', ', $errors));
    }
}
