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

class RobotsEntryBehaviorTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-sitemap');
    }

    /** @test */
    public function disabled_entries_are_not_included()
    {
        $this->extend(
            (new Robots())
                ->addEntry(TestDisabledEntry::class)
        );

        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();

        // Should NOT contain rules from disabled entry
        $this->assertStringNotContainsString('Disallow: /disabled-path', $content);
    }

    /** @test */
    public function entries_can_use_settings()
    {
        $this->setting('test.robots.enabled', true);

        $this->extend(
            (new Robots())
                ->addEntry(TestSettingsBasedEntry::class)
        );

        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();

        // Should contain rules when setting is enabled
        $this->assertStringContainsString('Disallow: /settings-based', $content);
    }

    /** @test */
    public function entries_respect_settings_changes()
    {
        $this->setting('test.robots.enabled', false);

        $this->extend(
            (new Robots())
                ->addEntry(TestSettingsBasedEntry::class)
        );

        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();

        // Should NOT contain rules when setting is disabled
        $this->assertStringNotContainsString('Disallow: /settings-based', $content);
    }

    /** @test */
    public function entries_can_return_empty_rules()
    {
        $this->extend(
            (new Robots())
                ->addEntry(TestEmptyRulesEntry::class)
        );

        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        // Should still return valid response even with empty rules
        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->getBody()->getContents();
        $this->assertStringContainsString('User-agent: *', $content);
    }
}

class TestDisabledEntry extends RobotsEntry
{
    public function getRules(): array
    {
        return [
            $this->disallowForAll('/disabled-path'),
        ];
    }

    public function enabled(): bool
    {
        return false;
    }
}

class TestSettingsBasedEntry extends RobotsEntry
{
    public function getRules(): array
    {
        return [
            $this->disallowForAll('/settings-based'),
        ];
    }

    public function enabled(): bool
    {
        return (bool) static::$settings->get('test.robots.enabled', false);
    }
}

class TestEmptyRulesEntry extends RobotsEntry
{
    public function getRules(): array
    {
        return [];
    }
}
