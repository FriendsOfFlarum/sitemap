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

namespace FoF\Sitemap\Tests\integration\console;

use Carbon\Carbon;
use Flarum\Testing\integration\ConsoleTestCase;
use FoF\Sitemap\Extend\Sitemap;
use FoF\Sitemap\Tests\integration\XmlSitemapTestTrait;

class CachedModeTest extends ConsoleTestCase
{
    use XmlSitemapTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-sitemap');

        $this->prepareDatabase([
            'discussions' => [
                [
                    'id'             => 1,
                    'title'          => 'Test Discussion',
                    'created_at'     => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(),
                    'last_posted_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(),
                    'user_id'        => 1,
                    'first_post_id'  => 1,
                    'comment_count'  => 1,
                    'is_private'     => 0,
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
    public function sitemap_build_command_exists()
    {
        $input = [
            'command' => 'list',
        ];

        $output = $this->runCommand($input);

        // The fof:sitemap:build command should be listed
        $this->assertStringContainsString('fof:sitemap:build', $output);
    }

    /**
     * @test
     */
    public function sitemap_build_command_runs_without_errors()
    {
        $input = [
            'command' => 'fof:sitemap:build',
        ];

        $output = $this->runCommand($input);

        // The command should complete without errors
        $this->assertStringNotContainsString('error', strtolower($output));
        $this->assertStringNotContainsString('exception', strtolower($output));
        $this->assertStringNotContainsString('failed', strtolower($output));

        // Should contain completion message
        $this->assertStringContainsString('Completed', $output);
    }

    /**
     * @test
     */
    public function cached_mode_generates_and_serves_sitemaps()
    {
        // Set the extension to cached multi-file mode
        $this->setting('fof-sitemap.mode', 'multi-file');

        // Run the sitemap build command
        $input = [
            'command' => 'fof:sitemap:build',
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
        $this->assertNotEmpty($indexBody, 'Cached sitemap index should not be empty');

        // Validate the cached sitemap structure
        $this->assertValidSitemapIndexXml($indexBody);

        $sitemapUrls = $this->getSitemapUrls($indexBody);
        $this->assertGreaterThan(0, count($sitemapUrls), 'Cached sitemap should contain sitemap URLs');

        // Test individual cached sitemap files
        $foundDiscussionUrl = false;
        $foundUserUrl = false;

        foreach ($sitemapUrls as $sitemapUrl) {
            $sitemapPath = parse_url($sitemapUrl, PHP_URL_PATH);
            $sitemapResponse = $this->send($this->request('GET', $sitemapPath));

            if ($sitemapResponse->getStatusCode() !== 200) {
                continue;
            }

            $sitemapBody = $sitemapResponse->getBody()->getContents();

            if (empty($sitemapBody)) {
                continue;
            }

            $this->assertValidSitemapXml($sitemapBody);
            $urls = $this->getUrlsFromSitemap($sitemapBody);

            foreach ($urls as $url) {
                if (preg_match('/\/d\/\d+/', $url)) {
                    $foundDiscussionUrl = true;
                }
                if (preg_match('/\/u\/\w+/', $url)) {
                    $foundUserUrl = true;
                }
            }
        }

        $this->assertTrue($foundDiscussionUrl, 'Cached sitemap should include discussion URLs');
        $this->assertTrue($foundUserUrl, 'Cached sitemap should include user URLs');
    }

    /**
     * @test
     */
    public function unified_extender_can_force_cached_mode()
    {
        $this->extend(
            (new Sitemap())
                ->forceCached()
        );

        // Run the sitemap build command
        $input = [
            'command' => 'fof:sitemap:build',
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
        $this->assertNotEmpty($indexBody, 'Unified extender forced cached sitemap index should not be empty');

        // Validate the cached sitemap structure
        $this->assertValidSitemapIndexXml($indexBody);

        $sitemapUrls = $this->getSitemapUrls($indexBody);
        $this->assertGreaterThan(0, count($sitemapUrls), 'Unified extender forced cached sitemap should contain sitemap URLs');

        // Verify that the container has the forced cached flag
        $container = $this->app()->getContainer();
        $this->assertTrue($container->has('fof-sitemaps.forceCached'));
        $this->assertTrue($container->get('fof-sitemaps.forceCached'));
    }

    /**
     * @test
     */
    public function unified_extender_forced_cached_mode_overrides_setting()
    {
        // Set the extension to runtime mode via setting
        $this->setting('fof-sitemap.mode', 'run');

        // But force cached mode via unified extender
        $this->extend(
            (new Sitemap())
                ->forceCached()
        );

        // Run the sitemap build command
        $input = [
            'command' => 'fof:sitemap:build',
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
        $this->assertNotEmpty($indexBody, 'Unified extender forced cached mode should override setting');
        $this->assertValidSitemapIndexXml($indexBody);
    }

    /**
     * @test
     */
    public function cached_mode_creates_physical_files_on_disk()
    {
        // Set the extension to cached multi-file mode
        $this->setting('fof-sitemap.mode', 'multi-file');

        // Run the sitemap build command
        $input = [
            'command' => 'fof:sitemap:build',
        ];

        $output = $this->runCommand($input);

        // The command should complete successfully
        $this->assertStringNotContainsString('error', strtolower($output));
        $this->assertStringContainsString('Completed', $output);

        // Check that physical files exist on disk
        $publicPath = $this->app()->getContainer()->get('flarum.paths')->public;
        $sitemapsPath = $publicPath.'/sitemaps';

        // The sitemaps directory should exist
        $this->assertTrue(is_dir($sitemapsPath), 'Sitemaps directory should exist on disk');

        // There should be sitemap files
        $files = glob($sitemapsPath.'/sitemap*.xml');
        $this->assertGreaterThan(0, count($files), 'Should have sitemap XML files on disk');

        // Check for index file
        $indexFile = $sitemapsPath.'/sitemap.xml';
        $this->assertTrue(file_exists($indexFile), 'Sitemap index file should exist on disk');

        // Verify index file content
        $indexContent = file_get_contents($indexFile);
        $this->assertNotEmpty($indexContent, 'Index file should not be empty');
        $this->assertValidSitemapIndexXml($indexContent);

        // Check individual sitemap files
        foreach ($files as $file) {
            if (basename($file) !== 'sitemap.xml') { // Skip the index file
                $content = file_get_contents($file);
                $this->assertNotEmpty($content, 'Sitemap file should not be empty: '.basename($file));
                $this->assertValidSitemapXml($content);
            }
        }
    }

    /**
     * @test
     */
    public function unified_extender_forced_cached_mode_creates_physical_files()
    {
        $this->extend(
            (new Sitemap())
                ->forceCached()
        );

        // Run the sitemap build command
        $input = [
            'command' => 'fof:sitemap:build',
        ];

        $output = $this->runCommand($input);

        // The command should complete successfully
        $this->assertStringNotContainsString('error', strtolower($output));
        $this->assertStringContainsString('Completed', $output);

        // Check that physical files exist on disk
        $publicPath = $this->app()->getContainer()->get('flarum.paths')->public;
        $sitemapsPath = $publicPath.'/sitemaps';

        // The sitemaps directory should exist
        $this->assertTrue(is_dir($sitemapsPath), 'Forced cached mode should create sitemaps directory on disk');

        // There should be sitemap files
        $files = glob($sitemapsPath.'/sitemap*.xml');
        $this->assertGreaterThan(0, count($files), 'Forced cached mode should create sitemap XML files on disk');

        // Check for index file
        $indexFile = $sitemapsPath.'/sitemap.xml';
        $this->assertTrue(file_exists($indexFile), 'Forced cached mode should create sitemap index file on disk');

        // Verify the container flag is set
        $container = $this->app()->getContainer();
        $this->assertTrue($container->has('fof-sitemaps.forceCached'));
        $this->assertTrue($container->get('fof-sitemaps.forceCached'));
    }
}
