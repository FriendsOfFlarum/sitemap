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

namespace FoF\Sitemap\Tests\Unit\Robots;

use Flarum\Testing\unit\TestCase;
use FoF\Sitemap\Robots\RobotsEntry;

class RobotsEntryHelpersTest extends TestCase
{
    /** @test */
    public function helper_methods_create_correct_rule_structures()
    {
        $entry = new TestRobotsEntryForHelpers();

        $this->assertEquals(
            ['user_agent' => '*', 'disallow' => '/test'],
            $entry->testDisallowForAll('/test')
        );

        $this->assertEquals(
            ['user_agent' => 'Googlebot', 'disallow' => '/test'],
            $entry->testDisallowFor('Googlebot', '/test')
        );

        $this->assertEquals(
            ['user_agent' => '*', 'allow' => '/test'],
            $entry->testAllowForAll('/test')
        );

        $this->assertEquals(
            ['user_agent' => 'Googlebot', 'allow' => '/test'],
            $entry->testAllowFor('Googlebot', '/test')
        );

        $this->assertEquals(
            ['user_agent' => '*', 'crawl_delay' => 10],
            $entry->testCrawlDelayForAll(10)
        );

        $this->assertEquals(
            ['user_agent' => 'Googlebot', 'crawl_delay' => 10],
            $entry->testCrawlDelayFor('Googlebot', 10)
        );

        $this->assertEquals(
            ['sitemap' => 'https://example.com/sitemap.xml'],
            $entry->testSitemap('https://example.com/sitemap.xml')
        );
    }
}

class TestRobotsEntryForHelpers extends RobotsEntry
{
    public function getRules(): array
    {
        return [];
    }

    // Expose protected methods for testing
    public function testDisallowForAll(string $path): array
    {
        return $this->disallowForAll($path);
    }

    public function testDisallowFor(string $userAgent, string $path): array
    {
        return $this->disallowFor($userAgent, $path);
    }

    public function testAllowForAll(string $path): array
    {
        return $this->allowForAll($path);
    }

    public function testAllowFor(string $userAgent, string $path): array
    {
        return $this->allowFor($userAgent, $path);
    }

    public function testCrawlDelayForAll(int $seconds): array
    {
        return $this->crawlDelayForAll($seconds);
    }

    public function testCrawlDelayFor(string $userAgent, int $seconds): array
    {
        return $this->crawlDelayFor($userAgent, $seconds);
    }

    public function testSitemap(string $url): array
    {
        return $this->sitemap($url);
    }
}
