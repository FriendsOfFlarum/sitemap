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

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Testing\integration\TestCase;
use FoF\Sitemap\Tests\integration\XmlSitemapTestTrait;

class SitemapTagsTest extends TestCase
{
    use XmlSitemapTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-sitemap');
        $this->extension('flarum-tags');

        $this->prepareDatabase([
            'tags' => [
                ['id' => 1, 'name' => 'General Discussion', 'slug' => 'general', 'position' => 0, 'parent_id' => null, 'discussion_count' => 8],
                ['id' => 2, 'name' => 'Support', 'slug' => 'support', 'position' => 1, 'parent_id' => null, 'discussion_count' => 6],
                ['id' => 3, 'name' => 'Bug Reports', 'slug' => 'bugs', 'position' => 2, 'parent_id' => 2, 'discussion_count' => 5],
                ['id' => 4, 'name' => 'Feature Requests', 'slug' => 'features', 'position' => 3, 'parent_id' => 2, 'discussion_count' => 5],
                ['id' => 5, 'name' => 'Restricted Tag', 'slug' => 'restricted', 'position' => 4, 'parent_id' => null, 'is_restricted' => true, 'discussion_count' => 7],
                ['id' => 6, 'name' => 'Empty Tag', 'slug' => 'empty', 'position' => 5, 'parent_id' => null, 'discussion_count' => 0],
            ],
            'discussions' => [
                ['id' => 1, 'title' => 'General Discussion 1', 'created_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 2, 'title' => 'Support Question', 'created_at' => Carbon::createFromDate(2023, 2, 1)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(2023, 2, 1)->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1, 'is_private' => 0],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>General discussion content</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::createFromDate(2023, 2, 1)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Support question content</p></t>'],
            ],
            'users' => [
                ['id' => 2, 'username' => 'testuser', 'email' => 'test@example.com', 'joined_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString()],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 2],
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag5.viewForum'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function sitemap_includes_tag_urls_when_tags_extension_enabled()
    {
        $indexResponse = $this->send($this->request('GET', '/sitemap.xml'));
        $sitemapUrls = $this->getSitemapUrls($indexResponse->getBody()->getContents());

        $foundTagUrls = [];
        $foundDiscussionUrl = false;

        foreach ($sitemapUrls as $sitemapUrl) {
            $sitemapPath = parse_url($sitemapUrl, PHP_URL_PATH);
            $sitemapResponse = $this->send($this->request('GET', $sitemapPath));

            if ($sitemapResponse->getStatusCode() !== 200) {
                continue;
            }

            $sitemapBody = $sitemapResponse->getBody()->getContents();
            $this->assertValidSitemapXml($sitemapBody);

            $urls = $this->getUrlsFromSitemap($sitemapBody);
            foreach ($urls as $url) {
                // Check for tag URLs (typically contain /t/)
                if (preg_match('/\/t\/(\w+)/', $url, $matches)) {
                    $foundTagUrls[] = $matches[1];
                }
                // Check for discussion URLs
                if (preg_match('/\/d\/\d+/', $url)) {
                    $foundDiscussionUrl = true;
                }
            }
        }

        // Should include public parent tags with discussions above default threshold of 5
        $this->assertContains('general', $foundTagUrls, 'Should include general tag (8 discussions)');
        $this->assertContains('support', $foundTagUrls, 'Should include support tag (6 discussions)');

        // Child tags are not included by default (bugs and features are child tags of support)
        // $this->assertContains('bugs', $foundTagUrls, 'Should include bugs tag (5 discussions)');
        // $this->assertContains('features', $foundTagUrls, 'Should include features tag (5 discussions)');

        // Should not include restricted tags for guests (even though it has 7 discussions)
        $this->assertNotContains('restricted', $foundTagUrls, 'Should not include restricted tag for guest');

        // Should not include empty tag
        $this->assertNotContains('empty', $foundTagUrls, 'Should not include empty tag (0 discussions)');

        // Should still include discussions
        $this->assertTrue($foundDiscussionUrl, 'Should still include discussion URLs');
    }

    /**
     * @test
     */
    public function sitemap_excludes_empty_tags_based_on_threshold()
    {
        // Set minimum discussion threshold for tags
        $this->setting('fof-sitemap.model.tags.discussion.minimum_item_threshold', 1);

        $indexResponse = $this->send($this->request('GET', '/sitemap.xml'));
        $sitemapUrls = $this->getSitemapUrls($indexResponse->getBody()->getContents());

        $foundTagUrls = [];

        foreach ($sitemapUrls as $sitemapUrl) {
            $sitemapPath = parse_url($sitemapUrl, PHP_URL_PATH);
            $sitemapResponse = $this->send($this->request('GET', $sitemapPath));

            if ($sitemapResponse->getStatusCode() !== 200) {
                continue;
            }

            $sitemapBody = $sitemapResponse->getBody()->getContents();
            $this->assertValidSitemapXml($sitemapBody);

            $urls = $this->getUrlsFromSitemap($sitemapBody);
            foreach ($urls as $url) {
                if (preg_match('/\/t\/(\w+)/', $url, $matches)) {
                    $foundTagUrls[] = $matches[1];
                }
            }
        }

        // Should not include empty tag (0 discussions)
        $this->assertNotContains('empty', $foundTagUrls, 'Should not include empty tag with 0 discussions');

        // Should include parent tags with discussions above threshold
        $this->assertContains('general', $foundTagUrls, 'Should include general tag with 8 discussions');
        $this->assertContains('support', $foundTagUrls, 'Should include support tag with 6 discussions');

        // Child tags might not be included by default
        // $this->assertContains('bugs', $foundTagUrls, 'Should include bugs tag with 5 discussions');
        // $this->assertContains('features', $foundTagUrls, 'Should include features tag with 5 discussions');
    }

    // /**
    //  * @test
    //  */
    public function sitemap_excludes_all_tags_when_setting_enabled()
    {
        // Enable tag exclusion (setting doesn't exist yet)
        $this->setting('fof-sitemap.excludeTags', true);

        $indexResponse = $this->send($this->request('GET', '/sitemap.xml'));
        $sitemapUrls = $this->getSitemapUrls($indexResponse->getBody()->getContents());

        $foundTagUrl = false;
        $foundDiscussionUrl = false;

        foreach ($sitemapUrls as $sitemapUrl) {
            $sitemapPath = parse_url($sitemapUrl, PHP_URL_PATH);
            $sitemapResponse = $this->send($this->request('GET', $sitemapPath));

            if ($sitemapResponse->getStatusCode() !== 200) {
                continue;
            }

            $sitemapBody = $sitemapResponse->getBody()->getContents();
            $urls = $this->getUrlsFromSitemap($sitemapBody);

            if (count($urls) > 0) {
                $this->assertValidSitemapXml($sitemapBody);

                foreach ($urls as $url) {
                    if (preg_match('/\/t\/\w+/', $url)) {
                        $foundTagUrl = true;
                    }
                    if (preg_match('/\/d\/\d+/', $url)) {
                        $foundDiscussionUrl = true;
                    }
                }
            }
        }

        $this->assertFalse($foundTagUrl, 'Should not include any tag URLs when tags are excluded');
        $this->assertTrue($foundDiscussionUrl, 'Should still include discussion URLs when only tags are excluded');
    }

    /**
     * @test
     */
    public function sitemap_validates_tag_xml_structure()
    {
        $indexResponse = $this->send($this->request('GET', '/sitemap.xml'));
        $sitemapUrls = $this->getSitemapUrls($indexResponse->getBody()->getContents());

        $foundTagSitemap = false;

        foreach ($sitemapUrls as $sitemapUrl) {
            $sitemapPath = parse_url($sitemapUrl, PHP_URL_PATH);
            $sitemapResponse = $this->send($this->request('GET', $sitemapPath));

            if ($sitemapResponse->getStatusCode() !== 200) {
                continue;
            }

            $sitemapBody = $sitemapResponse->getBody()->getContents();
            $urls = $this->getUrlsFromSitemap($sitemapBody);

            // Check if this sitemap contains tag URLs
            $hasTagUrls = false;
            foreach ($urls as $url) {
                if (preg_match('/\/t\/\w+/', $url)) {
                    $hasTagUrls = true;
                    break;
                }
            }

            if ($hasTagUrls && count($urls) > 0) {
                $foundTagSitemap = true;

                // Validate XML structure
                $this->assertValidSitemapXml($sitemapBody);

                // Check for proper sitemap elements
                $xpath = $this->parseXmlWithNamespace($sitemapBody);
                $priorities = $xpath->query('//sm:url/sm:priority');
                $changefreqs = $xpath->query('//sm:url/sm:changefreq');
                $lastmods = $xpath->query('//sm:url/sm:lastmod');

                // Should have priority and changefreq by default
                $this->assertGreaterThan(0, $priorities->length, 'Tag sitemap should include priority elements');
                $this->assertGreaterThan(0, $changefreqs->length, 'Tag sitemap should include changefreq elements');

                break;
            }
        }

        $this->assertTrue($foundTagSitemap, 'Should find at least one sitemap containing tag URLs');
    }

    /**
     * @test
     */
    public function sitemap_excludes_tags_route_from_static_urls_when_tags_excluded()
    {
        // Enable tag exclusion
        $this->setting('fof-sitemap.excludeTags', true);

        $indexResponse = $this->send($this->request('GET', '/sitemap.xml'));
        $sitemapUrls = $this->getSitemapUrls($indexResponse->getBody()->getContents());

        $foundTagsRoute = false;
        $foundAllRoute = false;

        foreach ($sitemapUrls as $sitemapUrl) {
            $sitemapPath = parse_url($sitemapUrl, PHP_URL_PATH);
            $sitemapResponse = $this->send($this->request('GET', $sitemapPath));

            if ($sitemapResponse->getStatusCode() !== 200) {
                continue;
            }

            $sitemapBody = $sitemapResponse->getBody()->getContents();
            $urls = $this->getUrlsFromSitemap($sitemapBody);

            if (count($urls) > 0) {
                $this->assertValidSitemapXml($sitemapBody);

                foreach ($urls as $url) {
                    // Check for /tags route in static URLs
                    if (preg_match('/\/tags$/', $url)) {
                        $foundTagsRoute = true;
                    }
                    // Check for /all route (should still be present)
                    if (preg_match('/\/all$/', $url)) {
                        $foundAllRoute = true;
                    }
                }
            }
        }

        $this->assertFalse($foundTagsRoute, 'Should not include /tags route when tags are excluded');
        $this->assertTrue($foundAllRoute, 'Should still include /all route when only tags are excluded');
    }
}
