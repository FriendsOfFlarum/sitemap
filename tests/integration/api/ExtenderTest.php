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

namespace FoF\Sitemap\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Testing\integration\TestCase;
use FoF\Sitemap\Extend\Sitemap;
use FoF\Sitemap\Tests\integration\TestDiscussionResource;
use FoF\Sitemap\Tests\integration\TestResource;
use FoF\Sitemap\Tests\integration\XmlSitemapTestTrait;

class ExtenderTest extends TestCase
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
                ['id' => 2, 'username' => 'testuser', 'email' => 'test@example.com', 'joined_at' => Carbon::createFromDate(
                    2023,
                    1,
                    1
                )->toDateTimeString(), 'comment_count' => 10],
            ],
        ]);
    }

    /**
     * @test
     */
    public function unified_extender_can_remove_existing_resource()
    {
        $this->extend(
            (new Sitemap())
                ->removeResource(\FoF\Sitemap\Resources\Discussion::class)
        );

        $indexResponse = $this->send($this->request('GET', '/sitemap.xml'));
        $sitemapUrls = $this->getSitemapUrls($indexResponse->getBody()->getContents());

        $foundUserUrl = false;
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
                    if (preg_match('/\/u\/\w+/', $url)) {
                        $foundUserUrl = true;
                    }
                    if (preg_match('/\/d\/\d+/', $url)) {
                        $foundDiscussionUrl = true;
                    }
                }
            }
        }

        $this->assertTrue($foundUserUrl, 'Unified extender should still include user URLs when Discussion resource is removed');
        $this->assertFalse($foundDiscussionUrl, 'Unified extender should not include discussion URLs when Discussion resource is removed');
    }

    /**
     * @test
     */
    public function unified_extender_can_add_custom_resource()
    {
        $this->extend(
            (new Sitemap())
                ->addResource(TestResource::class)
        );

        $indexResponse = $this->send($this->request('GET', '/sitemap.xml'));
        $indexBody = $indexResponse->getBody()->getContents();

        $this->assertNotEmpty($indexBody, 'Sitemap index should not be empty');

        $sitemapUrls = $this->getSitemapUrls($indexBody);

        $foundCustomUrl = false;
        $foundDiscussionUrl = false;

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

            $urls = $this->getUrlsFromSitemap($sitemapBody);

            if (count($urls) > 0) {
                $this->assertValidSitemapXml($sitemapBody);

                foreach ($urls as $url) {
                    if (strpos($url, '/test-resource/user-') !== false) {
                        $foundCustomUrl = true;
                    }
                    if (preg_match('/\/d\/\d+/', $url)) {
                        $foundDiscussionUrl = true;
                    }
                }
            }
        }

        $this->assertTrue($foundCustomUrl, 'Unified extender should include custom resource URLs');
        $this->assertTrue($foundDiscussionUrl, 'Unified extender should still include existing resources when adding custom resource');
    }

    /**
     * @test
     */
    public function unified_extender_validates_resource_inheritance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('has to extend');

        (new Sitemap())->addResource(\stdClass::class);
    }

    /**
     * @test
     */
    public function unified_extender_can_replace_existing_resource()
    {
        $this->extend(
            (new Sitemap())
                ->replaceResource(\FoF\Sitemap\Resources\Discussion::class, TestDiscussionResource::class)
        );

        $indexResponse = $this->send($this->request('GET', '/sitemap.xml'));
        $indexBody = $indexResponse->getBody()->getContents();

        $this->assertNotEmpty($indexBody, 'Sitemap index should not be empty');

        $sitemapUrls = $this->getSitemapUrls($indexBody);

        $foundCustomDiscussionUrl = false;
        $foundOriginalDiscussionUrl = false;
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

            $urls = $this->getUrlsFromSitemap($sitemapBody);

            if (count($urls) > 0) {
                $this->assertValidSitemapXml($sitemapBody);

                foreach ($urls as $url) {
                    // Check for custom discussion URL pattern
                    if (strpos($url, '/custom-discussion/') !== false) {
                        $foundCustomDiscussionUrl = true;
                    }
                    // Check for original discussion URL pattern
                    if (preg_match('/\/d\/\d+/', $url)) {
                        $foundOriginalDiscussionUrl = true;
                    }
                    // Check for user URLs (should still be present)
                    if (preg_match('/\/u\/\w+/', $url)) {
                        $foundUserUrl = true;
                    }
                }
            }
        }

        $this->assertTrue($foundCustomDiscussionUrl, 'Unified extender should include custom discussion URLs from replacement resource');
        $this->assertFalse($foundOriginalDiscussionUrl, 'Unified extender should not include original discussion URLs when Discussion resource is replaced');
        $this->assertTrue($foundUserUrl, 'Unified extender should still include other resources when Discussion resource is replaced');
    }

    /**
     * @test
     */
    public function unified_extender_can_add_static_url()
    {
        // First register a custom route that we can reference
        $this->extend(
            (new \Flarum\Extend\Routes('forum'))
                ->get('/test-static-page', 'test.static.route', function () {
                    return new \Laminas\Diactoros\Response\HtmlResponse('<h1>Test Static Page</h1>');
                }),
            (new Sitemap())
                ->addStaticUrl('test.static.route')
        );

        $indexResponse = $this->send($this->request('GET', '/sitemap.xml'));
        $indexBody = $indexResponse->getBody()->getContents();

        $this->assertNotEmpty($indexBody, 'Sitemap index should not be empty');

        $sitemapUrls = $this->getSitemapUrls($indexBody);

        $foundStaticUrl = false;
        $foundDiscussionUrl = false;

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

            $urls = $this->getUrlsFromSitemap($sitemapBody);

            if (count($urls) > 0) {
                $this->assertValidSitemapXml($sitemapBody);

                foreach ($urls as $url) {
                    // Look for our custom static route URL
                    if (strpos($url, '/test-static-page') !== false) {
                        $foundStaticUrl = true;
                    }
                    if (preg_match('/\/d\/\d+/', $url)) {
                        $foundDiscussionUrl = true;
                    }
                }
            }
        }

        $this->assertTrue($foundStaticUrl, 'Unified extender should include static URL from registered route');
        $this->assertTrue($foundDiscussionUrl, 'Unified extender should still include existing resources when adding static URLs');
    }
}
