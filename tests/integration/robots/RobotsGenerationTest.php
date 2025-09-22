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

namespace FoF\Sitemap\Tests\Integration\Robots;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use FoF\Sitemap\Tests\Integration\XmlSitemapTestTrait;

class RobotsGenerationTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use XmlSitemapTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-sitemap');
    }

    /** @test */
    public function robots_txt_returns_valid_response()
    {
        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain; charset=utf-8', $response->getHeaderLine('Content-Type'));
    }

    /** @test */
    public function robots_txt_contains_default_entries()
    {
        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();

        // Should contain user-agent declaration
        $this->assertStringContainsString('User-agent: *', $content);

        // Should contain admin disallow
        $this->assertStringContainsString('Disallow: /admin', $content);

        // Should contain API disallow
        $this->assertStringContainsString('Disallow: /api', $content);

        // Should contain auth-related disallows
        $this->assertStringContainsString('Disallow: /settings', $content);
        $this->assertStringContainsString('Disallow: /notifications', $content);
        $this->assertStringContainsString('Disallow: /logout', $content);

        // Should contain sitemap reference
        $this->assertStringContainsString('Sitemap:', $content);
    }

    /** @test */
    public function robots_txt_includes_sitemap_url()
    {
        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();

        // Just check that a sitemap URL is present, don't worry about the exact URL
        $this->assertStringContainsString('Sitemap:', $content);
        $this->assertStringContainsString('/sitemap.xml', $content);
    }

    /** @test */
    public function robots_txt_excludes_users_when_setting_enabled()
    {
        $this->setting('fof-sitemap.excludeUsers', true);

        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();

        // Should contain user profile disallow when users are excluded
        $this->assertStringContainsString('Disallow: /u/', $content);
    }

    /** @test */
    public function robots_txt_includes_users_when_setting_disabled()
    {
        $this->setting('fof-sitemap.excludeUsers', false);

        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();

        // Should NOT contain user profile disallow when users are included
        $this->assertStringNotContainsString('Disallow: /u/', $content);
    }
}
