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
use Flarum\Testing\integration\TestCase;
use FoF\Sitemap\Tests\integration\XmlSitemapTestTrait;

class BasicTest extends TestCase
{
    use XmlSitemapTestTrait;
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-sitemap');

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => 'First Discussion', 'created_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 3, 'is_private' => 0],
                ['id' => 2, 'title' => 'Second Discussion', 'created_at' => Carbon::createFromDate(2023, 2, 1)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(2023, 2, 1)->toDateTimeString(), 'user_id' => 3, 'first_post_id' => 4, 'comment_count' => 2, 'is_private' => 0],
                ['id' => 3, 'title' => 'Third Discussion', 'created_at' => Carbon::createFromDate(2023, 3, 1)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(2023, 3, 1)->toDateTimeString(), 'user_id' => 4, 'first_post_id' => 6, 'comment_count' => 4, 'is_private' => 0],
                ['id' => 4, 'title' => 'Hidden Discussion', 'created_at' => Carbon::createFromDate(2023, 4, 1)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(2023, 4, 1)->toDateTimeString(), 'hidden_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 10, 'comment_count' => 1, 'is_private' => 0],
            ],
            'posts' => [
                // User 2 posts (6 total - above default threshold of 5)
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>User 2 post 1</p></t>'],
                ['id' => 2, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(2023, 1, 2)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>User 2 post 2</p></t>'],
                ['id' => 3, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(2023, 1, 3)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>User 2 post 3</p></t>'],
                ['id' => 10, 'discussion_id' => 4, 'created_at' => Carbon::createFromDate(2023, 4, 1)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>User 2 post 4</p></t>'],
                ['id' => 11, 'discussion_id' => 2, 'created_at' => Carbon::createFromDate(2023, 2, 5)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>User 2 post 5</p></t>'],
                ['id' => 12, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(2023, 3, 5)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>User 2 post 6</p></t>'],
                
                // User 3 posts (3 total - below default threshold of 5)
                ['id' => 4, 'discussion_id' => 2, 'created_at' => Carbon::createFromDate(2023, 2, 1)->toDateTimeString(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>User 3 post 1</p></t>'],
                ['id' => 5, 'discussion_id' => 2, 'created_at' => Carbon::createFromDate(2023, 2, 2)->toDateTimeString(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>User 3 post 2</p></t>'],
                ['id' => 13, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(2023, 3, 6)->toDateTimeString(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>User 3 post 3</p></t>'],
                
                // User 4 posts (8 total - well above default threshold)
                ['id' => 6, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(2023, 3, 1)->toDateTimeString(), 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>User 4 post 1</p></t>'],
                ['id' => 7, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(2023, 3, 2)->toDateTimeString(), 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>User 4 post 2</p></t>'],
                ['id' => 8, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(2023, 3, 3)->toDateTimeString(), 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>User 4 post 3</p></t>'],
                ['id' => 9, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(2023, 3, 4)->toDateTimeString(), 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>User 4 post 4</p></t>'],
                ['id' => 14, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(2023, 1, 6)->toDateTimeString(), 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>User 4 post 5</p></t>'],
                ['id' => 15, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(2023, 1, 7)->toDateTimeString(), 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>User 4 post 6</p></t>'],
                ['id' => 16, 'discussion_id' => 2, 'created_at' => Carbon::createFromDate(2023, 2, 6)->toDateTimeString(), 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>User 4 post 7</p></t>'],
                ['id' => 17, 'discussion_id' => 2, 'created_at' => Carbon::createFromDate(2023, 2, 7)->toDateTimeString(), 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>User 4 post 8</p></t>'],
                
                // User 5 posts (1 total - well below threshold)
                ['id' => 18, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(2023, 1, 8)->toDateTimeString(), 'user_id' => 5, 'type' => 'comment', 'content' => '<t><p>User 5 only post</p></t>'],
            ],
            'users' => [
                ['id' => 2, 'username' => 'user_6_posts', 'email' => 'user6@example.com', 'joined_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(), 'comment_count' => 6],
                ['id' => 3, 'username' => 'user_3_posts', 'email' => 'user3@example.com', 'joined_at' => Carbon::createFromDate(2023, 1, 2)->toDateTimeString(), 'comment_count' => 3],
                ['id' => 4, 'username' => 'user_8_posts', 'email' => 'user8@example.com', 'joined_at' => Carbon::createFromDate(2023, 1, 3)->toDateTimeString(), 'comment_count' => 8],
                ['id' => 5, 'username' => 'user_1_post', 'email' => 'user1@example.com', 'joined_at' => Carbon::createFromDate(2023, 1, 4)->toDateTimeString(), 'comment_count' => 1],
            ],
        ]);
    }

    /**
     * @test
     */
    public function sitemap_index_returns_valid_xml_structure()
    {
        $response = $this->send(
            $this->request('GET', '/sitemap.xml')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody()->getContents();
        
        // Validate XML structure comprehensively
        $this->assertValidSitemapIndexXml($body);
    }

    /**
     * @test
     */
    public function sitemap_includes_discussions_with_sample_data()
    {
        $response = $this->send(
            $this->request('GET', '/sitemap.xml')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody()->getContents();
        
        // Validate the sitemap index structure
        $this->assertValidSitemapIndexXml($body);
        
        // Check that we have sitemap entries
        $sitemapUrls = $this->getSitemapUrls($body);
        $this->assertGreaterThan(0, count($sitemapUrls), 'Should contain sitemap entries');
    }

    /**
     * @test
     */
    public function individual_sitemap_contains_valid_urls()
    {
        // First get the sitemap index
        $indexResponse = $this->send(
            $this->request('GET', '/sitemap.xml')
        );
        
        $sitemapUrls = $this->getSitemapUrls($indexResponse->getBody()->getContents());
        $this->assertGreaterThan(0, count($sitemapUrls), 'Should have at least one sitemap listed');
        
        // Get the first sitemap URL and fetch it
        $firstSitemapUrl = parse_url($sitemapUrls[0], PHP_URL_PATH);
        $sitemapResponse = $this->send(
            $this->request('GET', $firstSitemapUrl)
        );
        
        $this->assertEquals(200, $sitemapResponse->getStatusCode());
        $sitemapBody = $sitemapResponse->getBody()->getContents();
        
        // Validate against sitemap schema
        $this->assertValidSitemapXml($sitemapBody);
        
        // Check that URLs are present
        $urls = $this->getUrlsFromSitemap($sitemapBody);
        $this->assertGreaterThan(0, count($urls), 'Should contain URLs');
    }

    /**
     * @test
     */
    public function sitemap_includes_user_urls_with_sufficient_posts()
    {
        // With default threshold of 5, users 2 (6 posts) and 4 (8 posts) should be included
        // Users 3 (3 posts) and 5 (1 post) should be excluded
        
        $indexResponse = $this->send($this->request('GET', '/sitemap.xml'));
        $sitemapUrls = $this->getSitemapUrls($indexResponse->getBody()->getContents());
        
        $foundUsers = [];
        $foundDiscussionUrl = false;
        
        foreach ($sitemapUrls as $sitemapUrl) {
            $sitemapPath = parse_url($sitemapUrl, PHP_URL_PATH);
            $sitemapResponse = $this->send($this->request('GET', $sitemapPath));
            
            if ($sitemapResponse->getStatusCode() !== 200) continue;
            
            $sitemapBody = $sitemapResponse->getBody()->getContents();
            $this->assertValidSitemapXml($sitemapBody);
            
            $urls = $this->getUrlsFromSitemap($sitemapBody);
            foreach ($urls as $url) {
                if (preg_match('/\/u\/(\w+)/', $url, $matches)) {
                    $foundUsers[] = $matches[1];
                }
                if (preg_match('/\/d\/\d+/', $url)) {
                    $foundDiscussionUrl = true;
                }
            }
        }
        
        $this->assertContains('user_6_posts', $foundUsers, 'Should include user with 6 posts');
        $this->assertContains('user_8_posts', $foundUsers, 'Should include user with 8 posts');
        $this->assertNotContains('user_3_posts', $foundUsers, 'Should not include user with 3 posts');
        $this->assertNotContains('user_1_post', $foundUsers, 'Should not include user with 1 post');
        $this->assertTrue($foundDiscussionUrl, 'Should include discussion URLs in sitemap');
    }

    /**
     * @test
     */
    public function sitemap_respects_user_minimum_post_threshold_setting()
    {
        // Set a high threshold that our test users won't meet
        $this->setting('fof-sitemap.model.user.comments.minimum_item_threshold', 10);
        
        // First get the sitemap index
        $indexResponse = $this->send(
            $this->request('GET', '/sitemap.xml')
        );
        
        $sitemapUrls = $this->getSitemapUrls($indexResponse->getBody()->getContents());
        $this->assertGreaterThan(0, count($sitemapUrls), 'Should have at least one sitemap listed');
        
        // Check all sitemaps - should not find user URLs due to high threshold
        $foundUserUrl = false;
        
        foreach ($sitemapUrls as $sitemapUrl) {
            $sitemapPath = parse_url($sitemapUrl, PHP_URL_PATH);
            $sitemapResponse = $this->send(
                $this->request('GET', $sitemapPath)
            );
            
            if ($sitemapResponse->getStatusCode() !== 200) {
                continue;
            }
            
            $sitemapBody = $sitemapResponse->getBody()->getContents();
            
            // Skip validation if sitemap is empty (which is expected)
            $urls = $this->getUrlsFromSitemap($sitemapBody);
            if (count($urls) > 0) {
                $this->assertValidSitemapXml($sitemapBody);
                
                foreach ($urls as $url) {
                    if (preg_match('/\/u\/\w+/', $url)) {
                        $foundUserUrl = true;
                        break;
                    }
                }
            }
        }
        
        $this->assertFalse($foundUserUrl, 'Should not include user URLs when threshold is too high');
    }
}
