<?php

namespace FoF\Sitemap\Tests\integration\console;

use Carbon\Carbon;
use Flarum\Testing\integration\ConsoleTestCase;
use FoF\Sitemap\Tests\integration\XmlSitemapTestTrait;

class LegacyCachedModeTest extends ConsoleTestCase
{
    use XmlSitemapTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-sitemap');

        $this->prepareDatabase([
            'discussions' => [
                [
                    'id' => 1,
                    'title' => 'Test Discussion',
                    'created_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(),
                    'last_posted_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(),
                    'user_id' => 1,
                    'first_post_id' => 1,
                    'comment_count' => 1,
                    'is_private' => 0
                ],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Test content</p></t>'],
            ],
            'users' => [
                ['id' => 2, 'username' => 'testuser', 'email' => 'test@example.com', 'joined_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(), 'comment_count' => 10],
            ],
        ]);
    }

    /**
     * @test
     */
    public function legacy_extender_can_force_cached_mode()
    {
        $this->extend(
            new \FoF\Sitemap\Extend\ForceCached()
        );

        // Run the sitemap build command
        $input = [
            'command' => 'fof:sitemap:build'
        ];
        
        $output = $this->runCommand($input);
        
        // The command should complete successfully
        $this->assertStringNotContainsString('error', strtolower($output));
        $this->assertStringNotContainsString('exception', strtolower($output));
        $this->assertStringContainsString('Completed', $output);

        // Now test that the sitemap is served from cache
        $indexResponse = $this->send($this->request('GET', '/sitemap.xml'));
        $indexBody = $indexResponse->getBody()->getContents();
        
        $this->assertEquals(200, $indexResponse->getStatusCode());
        $this->assertNotEmpty($indexBody, 'Legacy extender forced cached sitemap index should not be empty');
        
        // Validate the cached sitemap structure
        $this->assertValidSitemapIndexXml($indexBody);
        
        $sitemapUrls = $this->getSitemapUrls($indexBody);
        $this->assertGreaterThan(0, count($sitemapUrls), 'Legacy extender forced cached sitemap should contain sitemap URLs');

        // Verify that the container has the forced cached flag
        $container = $this->app()->getContainer();
        $this->assertTrue($container->has('fof-sitemaps.forceCached'));
        $this->assertTrue($container->get('fof-sitemaps.forceCached'));
    }

    /**
     * @test
     */
    public function legacy_extender_forced_cached_mode_overrides_setting()
    {
        // Set the extension to runtime mode via setting
        $this->setting('fof-sitemap.mode', 'run');

        // But force cached mode via legacy extender
        $this->extend(
            new \FoF\Sitemap\Extend\ForceCached()
        );

        // Run the sitemap build command
        $input = [
            'command' => 'fof:sitemap:build'
        ];
        
        $output = $this->runCommand($input);
        
        // The command should complete successfully
        $this->assertStringNotContainsString('error', strtolower($output));
        $this->assertStringContainsString('Completed', $output);

        // Verify that the container has the forced cached flag (overriding the setting)
        $container = $this->app()->getContainer();
        $this->assertTrue($container->has('fof-sitemaps.forceCached'));
        $this->assertTrue($container->get('fof-sitemaps.forceCached'));

        // The sitemap should still be served from cache despite the 'run' setting
        $indexResponse = $this->send($this->request('GET', '/sitemap.xml'));
        $this->assertEquals(200, $indexResponse->getStatusCode());
        
        $indexBody = $indexResponse->getBody()->getContents();
        $this->assertNotEmpty($indexBody, 'Legacy extender forced cached mode should override setting');
        $this->assertValidSitemapIndexXml($indexBody);
    }

    /**
     * @test
     */
    public function legacy_extender_forced_cached_mode_creates_physical_files()
    {
        $this->extend(
            new \FoF\Sitemap\Extend\ForceCached()
        );

        // Run the sitemap build command
        $input = [
            'command' => 'fof:sitemap:build'
        ];
        
        $output = $this->runCommand($input);
        
        // The command should complete successfully
        $this->assertStringNotContainsString('error', strtolower($output));
        $this->assertStringContainsString('Completed', $output);

        // Check that physical files exist on disk
        $publicPath = $this->app()->getContainer()->get('flarum.paths')->public;
        $sitemapsPath = $publicPath . '/sitemaps';
        
        // The sitemaps directory should exist
        $this->assertTrue(is_dir($sitemapsPath), 'Legacy forced cached mode should create sitemaps directory on disk');
        
        // There should be sitemap files
        $files = glob($sitemapsPath . '/sitemap*.xml');
        $this->assertGreaterThan(0, count($files), 'Legacy forced cached mode should create sitemap XML files on disk');
        
        // Check for index file
        $indexFile = $sitemapsPath . '/sitemap.xml';
        $this->assertTrue(file_exists($indexFile), 'Legacy forced cached mode should create sitemap index file on disk');
        
        // Verify index file content is valid
        $indexContent = file_get_contents($indexFile);
        $this->assertNotEmpty($indexContent, 'Legacy cached index file should not be empty');
        $this->assertValidSitemapIndexXml($indexContent);
        
        // Verify the container flag is set
        $container = $this->app()->getContainer();
        $this->assertTrue($container->has('fof-sitemaps.forceCached'));
        $this->assertTrue($container->get('fof-sitemaps.forceCached'));
    }
}
