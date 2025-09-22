<?php

namespace FoF\Sitemap\Tests\Integration\Robots;

use Flarum\Testing\integration\TestCase;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;

class RobotsTagsTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-sitemap', 'flarum-tags');
    }

    /** @test */
    public function robots_txt_excludes_tags_when_setting_enabled()
    {
        $this->setting('fof-sitemap.excludeTags', true);

        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();

        // Should contain tag-related disallows when tags are excluded
        $this->assertStringContainsString('Disallow: /t/', $content);
        $this->assertStringContainsString('Disallow: /tags', $content);
    }

    /** @test */
    public function robots_txt_includes_tags_when_setting_disabled()
    {
        $this->setting('fof-sitemap.excludeTags', false);

        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();

        // Should NOT contain tag-related disallows when tags are included
        $this->assertStringNotContainsString('Disallow: /t/', $content);
        $this->assertStringNotContainsString('Disallow: /tags', $content);
    }

    /** @test */
    public function robots_txt_excludes_tags_without_tags_extension()
    {
        // Disable tags extension
        $this->app()->getContainer()->make('flarum.extensions')->disable('flarum-tags');

        $this->setting('fof-sitemap.excludeTags', true);

        $response = $this->send(
            $this->request('GET', '/robots.txt')
        );

        $content = $response->getBody()->getContents();

        // Should NOT contain tag-related disallows when tags extension is disabled
        $this->assertStringNotContainsString('Disallow: /t/', $content);
        $this->assertStringNotContainsString('Disallow: /tags', $content);
    }
}
