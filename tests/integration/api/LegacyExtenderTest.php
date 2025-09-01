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
use FoF\Sitemap\Extend\RegisterResource;
use FoF\Sitemap\Extend\RegisterStaticUrl;
use FoF\Sitemap\Extend\RemoveResource;
use FoF\Sitemap\Tests\integration\TestResource;
use FoF\Sitemap\Tests\integration\XmlSitemapTestTrait;

class LegacyExtenderTest extends TestCase
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
            new RemoveResource(\FoF\Sitemap\Resources\Discussion::class)
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

        $this->assertTrue($foundUserUrl, 'Legacy extender should still include user URLs when Discussion resource is removed');
        $this->assertFalse($foundDiscussionUrl, 'Legacy extender should not include discussion URLs when Discussion resource is removed');
    }

    /**
     * @test
     */
    public function legacy_extender_can_add_custom_resource()
    {
        $this->extend(
            new RegisterResource(TestResource::class)
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

        $this->assertTrue($foundCustomUrl, 'Legacy extender should include custom resource URLs');
        $this->assertTrue($foundDiscussionUrl, 'Legacy extender should still include existing resources when adding custom resource');
    }

    /**
     * @test
     */
    public function legacy_extender_validates_resource_inheritance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('has to extend');

        new RegisterResource(\stdClass::class);
    }

    /**
     * @test
     */
    public function legacy_extender_can_add_static_url()
    {
        // First register a custom route that we can reference
        $this->extend(
            (new \Flarum\Extend\Routes('forum'))
                ->get('/test-legacy-static-page', 'test.legacy.static.route', function () {
                    return new \Laminas\Diactoros\Response\HtmlResponse('<h1>Test Legacy Static Page</h1>');
                }),
            new RegisterStaticUrl('test.legacy.static.route')
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
                    if (strpos($url, '/test-legacy-static-page') !== false) {
                        $foundStaticUrl = true;
                    }
                    if (preg_match('/\/d\/\d+/', $url)) {
                        $foundDiscussionUrl = true;
                    }
                }
            }
        }

        $this->assertTrue($foundStaticUrl, 'Legacy extender should include static URL from registered route');
        $this->assertTrue($foundDiscussionUrl, 'Legacy extender should still include existing resources when adding static URLs');
    }
}
