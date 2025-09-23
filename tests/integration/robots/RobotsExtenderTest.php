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

class RobotsExtenderTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-sitemap');
    }

    /** @test */
    public function robots_extender_can_add_custom_entry()
    {
        $this->extend(
            (new Robots())
                ->addEntry(TestCustomRobotsEntry::class)
        );

        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();

        // Should contain custom entry rules
        $this->assertStringContainsString('Disallow: /custom-path', $content);
        $this->assertStringContainsString('Crawl-delay: 5', $content);
    }

    /** @test */
    public function robots_extender_can_remove_existing_entry()
    {
        $this->extend(
            (new Robots())
                ->removeEntry(\FoF\Sitemap\Robots\Entries\ApiEntry::class)
        );

        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();

        // Should NOT contain API disallow rules
        $this->assertStringNotContainsString('Disallow: /api', $content);
    }

    /** @test */
    public function robots_extender_can_replace_existing_entry()
    {
        $this->extend(
            (new Robots())
                ->replace(\FoF\Sitemap\Robots\Entries\AdminEntry::class, TestCustomAdminEntry::class)
        );

        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();

        // Should contain custom admin rules
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Allow: /admin/public', $content);
    }

    /** @test */
    public function robots_extender_validates_entry_classes()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Robots entry class InvalidClass does not exist');

        $this->extend(
            (new Robots())
                ->addEntry('InvalidClass')
        );
    }

    /** @test */
    public function robots_extender_validates_entry_inheritance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must extend RobotsEntry');

        $this->extend(
            (new Robots())
                ->addEntry(\stdClass::class)
        );
    }
}

class TestCustomRobotsEntry extends RobotsEntry
{
    public function getRules(): array
    {
        return [
            $this->disallowForAll('/custom-path'),
            $this->crawlDelayForAll(5),
        ];
    }
}

class TestCustomAdminEntry extends \FoF\Sitemap\Robots\Entries\AdminEntry
{
    protected function buildAdminRules(string $adminPath): array
    {
        return [
            $this->disallowForAll($adminPath),
            $this->allowForAll($adminPath.'/public'),
        ];
    }
}
