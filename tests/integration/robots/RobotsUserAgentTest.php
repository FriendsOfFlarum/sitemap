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
use FoF\Sitemap\Extend\Robots;
use FoF\Sitemap\Robots\RobotsEntry;

class RobotsUserAgentTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-sitemap');
    }

    /** @test */
    public function robots_txt_groups_rules_by_user_agent()
    {
        $this->extend(
            (new Robots())
                ->addEntry(TestMultiUserAgentEntry::class)
        );

        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();

        // Should contain multiple user-agent sections
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('User-agent: BadBot', $content);

        // Should contain specific rules for each user agent
        $this->assertStringContainsString('Crawl-delay: 10', $content);
        $this->assertStringContainsString('Allow: /special', $content);
        $this->assertStringContainsString('Disallow: /', $content);
    }

    /** @test */
    public function robots_txt_places_sitemaps_at_end()
    {
        $this->extend(
            (new Robots())
                ->addEntry(TestMultiUserAgentEntry::class)
        );

        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();
        $lines = explode("\n", trim($content));

        // Find the last non-empty line
        $lastLine = '';
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (trim($lines[$i]) !== '') {
                $lastLine = trim($lines[$i]);
                break;
            }
        }

        // Should end with sitemap directive
        $this->assertStringStartsWith('Sitemap:', $lastLine);
    }
}

class TestMultiUserAgentEntry extends RobotsEntry
{
    public function getRules(): array
    {
        return [
            $this->disallowForAll('/private'),
            $this->crawlDelayFor('Googlebot', 10),
            $this->allowFor('Googlebot', '/special'),
            $this->disallowFor('BadBot', '/'),
        ];
    }
}
