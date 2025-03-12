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

namespace FoF\Sitemap\Tests\integration\forum;

use Flarum\Testing\integration\TestCase;

class BasicTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-sitemap');
    }

    /**
     * @test
     */
    public function sitemap_is_available_in_runtime_mode()
    {
        $response = $this->send(
            $this->request('GET', '/sitemap.xml')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function sitemap_is_available_in_muti_file_mode()
    {
        $this->setting('fof-sitemap.mode', 'multi-file');

        $response = $this->send(
            $this->request('GET', '/sitemap.xml')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $response->getBody()->getContents());
    }
}
