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
use FoF\Sitemap\Tests\integration\XmlSitemapTestTrait;

/**
 * Memory stress tests for sitemap generation with large datasets.
 *
 * These tests verify that sitemap generation can handle large communities
 * without running out of memory. They are designed to replicate production
 * scenarios where forums may have 100,000+ discussions.
 *
 * The tests use environment variables to control dataset size:
 * - SITEMAP_STRESS_TEST_SMALL: 100k discussions (2 sets)
 * - SITEMAP_STRESS_TEST_MEDIUM: 250k discussions (5 sets)
 * - SITEMAP_STRESS_TEST_LARGE: 1 million discussions (20 sets)
 *
 * Set these environment variables to enable the respective tests.
 * By default, only the small test runs in CI to avoid timeouts.
 */
class MemoryStressTest extends ConsoleTestCase
{
    use XmlSitemapTestTrait;

    /**
     * Memory limits for stress tests (in bytes) by dataset size.
     * Tests will fail if memory usage exceeds these thresholds.
     *
     * Limits were reduced significantly after the streaming refactor:
     * - UrlSet no longer accumulates 50k Url objects in a $urls[] array (~15-20 MB/set).
     * - The XMLWriter in-memory buffer is flushed to a php://temp stream every 500 entries,
     *   capping its footprint to a few hundred KB instead of ~40 MB per set.
     * - storeSet() receives a stream resource; Disk/ProxyDisk pass it directly to
     *   Flysystem::put(), so no full XML string is ever materialised in PHP RAM.
     * - Only the Memory backend materialises the string (it is not intended for
     *   production-scale forums).
     */
    private const MEMORY_LIMITS = [
        100000  => 80 * 1024 * 1024,   // 80MB for 100k discussions (~2 sets)
        250000  => 100 * 1024 * 1024,  // 100MB for 250k discussions (~5 sets)
        1000000 => 150 * 1024 * 1024,  // 150MB for 1M discussions (~20 sets)
    ];

    /**
     * Maximum number of URLs per sitemap set (as defined in UrlSet).
     */
    private const URLS_PER_SET = 50000;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-sitemap');

        // Disable users and tags to focus on discussion memory usage
        $this->setting('fof-sitemap.excludeUsers', true);
        $this->setting('fof-sitemap.excludeTags', true);
    }

    /**
     * @test
     */
    public function small_dataset_memory_usage_stays_within_limits()
    {
        if (!getenv('SITEMAP_STRESS_TEST_SMALL')) {
            $this->markTestSkipped('Set SITEMAP_STRESS_TEST_SMALL=1 to run this test');
        }

        $discussionCount = 100000; // 2 UrlSets (100k discussions)

        $this->generateLargeDataset($discussionCount);

        $memoryBefore = memory_get_usage(true);
        $peakBefore = memory_get_peak_usage(true);

        $input = ['command' => 'fof:sitemap:build'];
        $output = $this->runCommand($input);

        $memoryAfter = memory_get_usage(true);
        $peakAfter = memory_get_peak_usage(true);

        $memoryUsed = $peakAfter - $peakBefore;
        $memoryUsedMB = round($memoryUsed / 1024 / 1024, 2);

        // Verify command completed successfully
        $this->assertStringContainsString('Completed', $output);
        $this->assertStringNotContainsString('error', strtolower($output));
        $this->assertStringNotContainsString('out of memory', strtolower($output));

        // Verify memory usage is reasonable
        $memoryLimit = self::MEMORY_LIMITS[$discussionCount];
        $memoryLimitMB = round($memoryLimit / 1024 / 1024, 2);
        $this->assertLessThan(
            $memoryLimit,
            $memoryUsed,
            "Memory usage ({$memoryUsedMB}MB) exceeded limit of {$memoryLimitMB}MB for {$discussionCount} discussions"
        );

        // Verify the sitemap was generated correctly
        $this->verifySitemapGeneration($discussionCount);
    }

    /**
     * @test
     */
    public function medium_dataset_memory_usage_stays_within_limits()
    {
        if (!getenv('SITEMAP_STRESS_TEST_MEDIUM')) {
            $this->markTestSkipped('Set SITEMAP_STRESS_TEST_MEDIUM=1 to run this test');
        }

        $discussionCount = 250000; // 5 UrlSets

        $this->generateLargeDataset($discussionCount);

        $memoryBefore = memory_get_usage(true);
        $peakBefore = memory_get_peak_usage(true);

        $input = ['command' => 'fof:sitemap:build'];
        $output = $this->runCommand($input);

        $memoryAfter = memory_get_usage(true);
        $peakAfter = memory_get_peak_usage(true);

        $memoryUsed = $peakAfter - $peakBefore;
        $memoryUsedMB = round($memoryUsed / 1024 / 1024, 2);

        // Verify command completed successfully
        $this->assertStringContainsString('Completed', $output);
        $this->assertStringNotContainsString('error', strtolower($output));
        $this->assertStringNotContainsString('out of memory', strtolower($output));

        // Verify memory usage is reasonable
        $memoryLimit = self::MEMORY_LIMITS[$discussionCount];
        $memoryLimitMB = round($memoryLimit / 1024 / 1024, 2);
        $this->assertLessThan(
            $memoryLimit,
            $memoryUsed,
            "Memory usage ({$memoryUsedMB}MB) exceeded limit of {$memoryLimitMB}MB for {$discussionCount} discussions"
        );

        // Verify the sitemap was generated correctly
        $this->verifySitemapGeneration($discussionCount);
    }

    /**
     * @test
     */
    public function large_dataset_memory_usage_stays_within_limits()
    {
        if (!getenv('SITEMAP_STRESS_TEST_LARGE')) {
            $this->markTestSkipped('Set SITEMAP_STRESS_TEST_LARGE=1 to run this test');
        }

        $discussionCount = 1000000; // 20 UrlSets - critical case for large communities

        $this->generateLargeDataset($discussionCount);

        $memoryBefore = memory_get_usage(true);
        $peakBefore = memory_get_peak_usage(true);

        $input = ['command' => 'fof:sitemap:build'];
        $output = $this->runCommand($input);

        $memoryAfter = memory_get_usage(true);
        $peakAfter = memory_get_peak_usage(true);

        $memoryUsed = $peakAfter - $peakBefore;
        $memoryUsedMB = round($memoryUsed / 1024 / 1024, 2);

        // Verify command completed successfully
        $this->assertStringContainsString('Completed', $output);
        $this->assertStringNotContainsString('error', strtolower($output));
        $this->assertStringNotContainsString('out of memory', strtolower($output));

        // Verify memory usage is reasonable for large dataset
        $memoryLimit = self::MEMORY_LIMITS[$discussionCount];
        $memoryLimitMB = round($memoryLimit / 1024 / 1024, 2);
        $this->assertLessThan(
            $memoryLimit,
            $memoryUsed,
            "Memory usage ({$memoryUsedMB}MB) exceeded limit of {$memoryLimitMB}MB for {$discussionCount} discussions"
        );

        // Verify the sitemap was generated correctly
        $this->verifySitemapGeneration($discussionCount);
    }

    /**
     * Generate a large dataset of discussions for stress testing.
     *
     * Uses batch inserts for performance. Each discussion has minimal data
     * to focus on memory issues related to volume rather than complexity.
     *
     * @param int $count Number of discussions to generate
     */
    private function generateLargeDataset(int $count): void
    {
        // Batch size limited by MySQL prepared statement placeholder limit (65535)
        // Each discussion has 9 fields (without first_post_id), each post has 6 fields = 15 total
        // 65535 / 15 = ~4369 records per batch, use 4000 to be safe
        $batchSize = 4000;
        $batches = ceil($count / $batchSize);

        $baseDate = Carbon::createFromDate(2020, 1, 1);

        // Temporarily disable foreign key checks to handle circular dependency
        $this->database()->statement('SET FOREIGN_KEY_CHECKS=0');

        for ($batch = 0; $batch < $batches; $batch++) {
            $discussions = [];
            $posts = [];

            $startId = $batch * $batchSize + 1;
            $endId = min($startId + $batchSize - 1, $count);

            for ($i = $startId; $i <= $endId; $i++) {
                $createdAt = $baseDate->copy()->addDays($i % 365)->toDateTimeString();

                // Create discussions without first_post_id initially
                $discussions[] = [
                    'id'             => $i,
                    'title'          => "Stress Test Discussion {$i}",
                    'slug'           => "stress-test-discussion-{$i}",
                    'created_at'     => $createdAt,
                    'last_posted_at' => $createdAt,
                    'user_id'        => 1,
                    'comment_count'  => 1,
                    'is_private'     => 0,
                ];

                $posts[] = [
                    'id'            => $i,
                    'discussion_id' => $i,
                    'created_at'    => $createdAt,
                    'user_id'       => 1,
                    'type'          => 'comment',
                    'content'       => '<t><p>Test content</p></t>',
                ];
            }

            // Insert both tables
            $this->database()->table('discussions')->insert($discussions);
            $this->database()->table('posts')->insert($posts);

            // Update discussions to set first_post_id
            $this->database()->statement(
                'UPDATE discussions SET first_post_id = id WHERE id >= ? AND id <= ?',
                [$startId, $endId]
            );
        }

        // Re-enable foreign key checks
        $this->database()->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Verify that the sitemap was generated correctly for the dataset.
     *
     * @param int $expectedUrlCount Expected number of discussion URLs
     */
    private function verifySitemapGeneration(int $expectedUrlCount): void
    {
        // Fetch the sitemap index
        $indexResponse = $this->send($this->request('GET', '/sitemap.xml'));
        $this->assertEquals(200, $indexResponse->getStatusCode());

        $indexBody = $indexResponse->getBody()->getContents();
        $this->assertValidSitemapIndexXml($indexBody);

        $sitemapUrls = $this->getSitemapUrls($indexBody);

        // Calculate expected number of sitemap files
        // +1 for static URLs, then ceil for discussions split across sets
        $expectedSitemapCount = 1 + ceil($expectedUrlCount / self::URLS_PER_SET);

        $this->assertGreaterThanOrEqual(
            $expectedSitemapCount,
            count($sitemapUrls),
            "Expected at least {$expectedSitemapCount} sitemap files for {$expectedUrlCount} discussions"
        );

        // Sample check: verify first sitemap contains valid URLs
        $firstSitemapUrl = parse_url($sitemapUrls[0], PHP_URL_PATH);
        $firstSitemapResponse = $this->send($this->request('GET', $firstSitemapUrl));
        $this->assertEquals(200, $firstSitemapResponse->getStatusCode());

        $firstSitemapBody = $firstSitemapResponse->getBody()->getContents();
        $this->assertValidSitemapXml($firstSitemapBody);

        $urls = $this->getUrlsFromSitemap($firstSitemapBody);
        $this->assertGreaterThan(0, count($urls), 'First sitemap should contain URLs');
    }

    /**
     * @test
     *
     * Replicates the exact production community that triggered the OOM:
     *   702k users + 81.5k discussions = ~784k sitemap entries (~16 sets).
     *
     * Users are the dominant resource here (702k >> 81.5k discussions), and the
     * User resource has a minimum comment_count threshold filter, so we seed users
     * with comment_count > 0 to ensure they all appear in the sitemap.
     *
     * Set SITEMAP_STRESS_TEST_PRODUCTION_REPLICA=1 to run this test.
     */
    public function production_replica_702k_users_81k_discussions_stays_within_limits()
    {
        if (!getenv('SITEMAP_STRESS_TEST_PRODUCTION_REPLICA')) {
            $this->markTestSkipped('Set SITEMAP_STRESS_TEST_PRODUCTION_REPLICA=1 to run this test');
        }

        $discussionCount = 81500;
        $userCount       = 702000;
        $totalUrls       = $discussionCount + $userCount; // ~784k

        // Use the Disk (file-backed) deploy backend so storeSet() streams directly to
        // disk rather than materialising all sets in RAM (which is what Memory does).
        // This matches how a production forum would be configured.
        $this->setting('fof-sitemap.mode', 'multi-file');

        // Enable both users and discussions (override setUp() which disables users)
        $this->setting('fof-sitemap.excludeUsers', false);
        $this->setting('fof-sitemap.excludeTags', true);
        $this->setting('fof-sitemap.model.user.comments.minimum_item_threshold', 0);

        $this->generateLargeDataset($discussionCount);
        $this->generateLargeUserDataset($userCount);

        $peakBefore = memory_get_peak_usage(true);

        $input  = ['command' => 'fof:sitemap:build'];
        $output = $this->runCommand($input);

        $peakAfter  = memory_get_peak_usage(true);
        $memoryUsed = $peakAfter - $peakBefore;
        $memoryUsedMB = round($memoryUsed / 1024 / 1024, 2);

        $this->assertStringContainsString('Completed', $output);
        $this->assertStringNotContainsString('error', strtolower($output));
        $this->assertStringNotContainsString('out of memory', strtolower($output));

        // 784k entries across ~16 sets using Disk backend (streams directly, no string copy).
        // Measured at ~154MB on reference hardware; 200MB gives ~30% headroom.
        $memoryLimit   = 200 * 1024 * 1024;
        $memoryLimitMB = 200;
        $this->assertLessThan(
            $memoryLimit,
            $memoryUsed,
            "Production-replica memory usage ({$memoryUsedMB}MB) exceeded limit of {$memoryLimitMB}MB for {$totalUrls} total URLs"
        );
    }

    /**
     * Generate a large dataset of users for stress testing.
     * Users start at id=2 (id=1 is the admin seeded by the test harness).
     */
    private function generateLargeUserDataset(int $count): void
    {
        // Each user row has 6 fields; 65535 / 6 = ~10922, use 8000 to be safe
        $batchSize = 8000;
        $batches   = ceil($count / $batchSize);
        $baseDate  = Carbon::createFromDate(2015, 1, 1);

        for ($batch = 0; $batch < $batches; $batch++) {
            $users    = [];
            $startId  = $batch * $batchSize + 2; // +2: id=1 is admin
            $endId    = min($startId + $batchSize - 1, $count + 1);

            for ($i = $startId; $i <= $endId; $i++) {
                $joinedAt = $baseDate->copy()->addDays($i % 3650)->toDateTimeString();
                $users[]  = [
                    'id'            => $i,
                    'username'      => "stressuser{$i}",
                    'email'         => "stressuser{$i}@example.com",
                    'joined_at'     => $joinedAt,
                    'last_seen_at'  => $joinedAt,
                    'comment_count' => 1,
                ];
            }

            $this->database()->table('users')->insert($users);
        }
    }

    /**
     * @test
     */
    public function baseline_memory_measurement_with_minimal_data()
    {
        // This test establishes a baseline memory usage with minimal data
        // to help identify memory issues in the stress tests above

        $this->prepareDatabase([
            'discussions' => [
                [
                    'id'             => 1,
                    'title'          => 'Baseline Discussion',
                    'created_at'     => Carbon::now()->toDateTimeString(),
                    'last_posted_at' => Carbon::now()->toDateTimeString(),
                    'user_id'        => 1,
                    'first_post_id'  => 1,
                    'comment_count'  => 1,
                    'is_private'     => 0,
                ],
            ],
            'posts' => [
                [
                    'id'            => 1,
                    'discussion_id' => 1,
                    'created_at'    => Carbon::now()->toDateTimeString(),
                    'user_id'       => 1,
                    'type'          => 'comment',
                    'content'       => '<t><p>Baseline content</p></t>',
                ],
            ],
        ]);

        $memoryBefore = memory_get_usage(true);
        $peakBefore = memory_get_peak_usage(true);

        $input = ['command' => 'fof:sitemap:build'];
        $output = $this->runCommand($input);

        $memoryAfter = memory_get_usage(true);
        $peakAfter = memory_get_peak_usage(true);

        $memoryUsed = $peakAfter - $peakBefore;
        $memoryUsedMB = round($memoryUsed / 1024 / 1024, 2);

        $this->assertStringContainsString('Completed', $output);

        // Baseline should use minimal memory (under 50MB)
        $this->assertLessThan(
            50 * 1024 * 1024, // 50MB
            $memoryUsed,
            "Baseline memory usage ({$memoryUsedMB}MB) seems too high for minimal data"
        );
    }
}
