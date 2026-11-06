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
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use FoF\Sitemap\Extend\Sitemap;
use FoF\Sitemap\Resources\StaticUrls;
use FoF\Sitemap\Tests\integration\ExcludeDiscussionQueryModifier;
use FoF\Sitemap\Tests\integration\TestDiscussionResource;
use FoF\Sitemap\Tests\integration\TestResource;
use FoF\Sitemap\Tests\integration\XmlSitemapTestTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ExtenderTest extends TestCase
{
    use XmlSitemapTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-sitemap');

        $this->prepareDatabase([
            Discussion::class => [
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
                [
                    'id'             => 2,
                    'title'          => 'Second Discussion',
                    'created_at'     => Carbon::createFromDate(2023, 1, 2)->toDateTimeString(),
                    'last_posted_at' => Carbon::createFromDate(2023, 1, 2)->toDateTimeString(),
                    'user_id'        => 1,
                    'first_post_id'  => 2,
                    'comment_count'  => 1,
                    'is_private'     => 0,
                ],
                [
                    'id'             => 3,
                    'title'          => 'Third Discussion',
                    'created_at'     => Carbon::createFromDate(2023, 1, 3)->toDateTimeString(),
                    'last_posted_at' => Carbon::createFromDate(2023, 1, 3)->toDateTimeString(),
                    'user_id'        => 1,
                    'first_post_id'  => 3,
                    'comment_count'  => 1,
                    'is_private'     => 0,
                ],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Test content</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::createFromDate(2023, 1, 2)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Test content</p></t>'],
                ['id' => 3, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(2023, 1, 3)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Test content</p></t>'],
            ],
            User::class => [
                ['id' => 2, 'username' => 'testuser', 'email' => 'test@example.com', 'joined_at' => Carbon::createFromDate(
                    2023,
                    1,
                    1
                )->toDateTimeString(), 'comment_count' => 10],
            ],
        ]);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function unified_extender_validates_resource_inheritance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('has to extend');

        (new Sitemap())->addResource(\stdClass::class);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function modify_resource_query_closure_filters_discussions()
    {
        $this->extend(
            (new Sitemap())
                ->modifyResourceQuery(\FoF\Sitemap\Resources\Discussion::class, fn (Builder $query) => $query->where('discussions.id', '!=', 2))
        );

        $this->assertEquals([1, 3], $this->getIdsFromUrls($this->getAllSitemapUrls(), '/d/'));
    }

    #[Test]
    public function modify_resource_query_modifiers_from_separate_extenders_stack()
    {
        $this->extend(
            (new Sitemap())
                ->modifyResourceQuery(\FoF\Sitemap\Resources\Discussion::class, function (Builder $query) {
                    $query->where('discussions.id', '!=', 2);
                }),
            (new Sitemap())
                ->modifyResourceQuery(\FoF\Sitemap\Resources\Discussion::class, fn (Builder $query) => $query->where('discussions.id', '!=', 3))
        );

        $this->assertEquals([1], $this->getIdsFromUrls($this->getAllSitemapUrls(), '/d/'));
    }

    public static function modifierRegisteredBeforeReplace(): array
    {
        return [
            'modifier registered before replace' => [true],
            'modifier registered after replace'  => [false],
        ];
    }

    #[Test]
    #[DataProvider('modifierRegisteredBeforeReplace')]
    public function modify_resource_query_applies_to_replacement_subclass(bool $modifierFirst)
    {
        $modifier = (new Sitemap())
            ->modifyResourceQuery(\FoF\Sitemap\Resources\Discussion::class, fn (Builder $query) => $query->where('discussions.id', '!=', 2));
        $replacement = (new Sitemap())
            ->replaceResource(\FoF\Sitemap\Resources\Discussion::class, TestDiscussionResource::class);

        $this->extend(...($modifierFirst ? [$modifier, $replacement] : [$replacement, $modifier]));

        $urls = $this->getAllSitemapUrls();

        $this->assertEquals([1, 3], $this->getIdsFromUrls($urls, '/custom-discussion/'));
        $this->assertEquals([], $this->getIdsFromUrls($urls, '/d/'), 'The replaced Discussion resource should not be in the sitemap');
    }

    #[Test]
    public function modify_resource_query_resolves_invokable_class_from_container()
    {
        $this->setting('fof-sitemap-test.excluded_discussion_id', '3');

        $this->extend(
            (new Sitemap())
                ->modifyResourceQuery(\FoF\Sitemap\Resources\Discussion::class, ExcludeDiscussionQueryModifier::class)
        );

        $this->assertEquals([1, 2], $this->getIdsFromUrls($this->getAllSitemapUrls(), '/d/'));
    }

    #[Test]
    public function modify_resource_query_can_replace_static_urls_collection()
    {
        $this->extend(
            (new \Flarum\Extend\Routes('forum'))
                ->get('/test-static-page', 'test.static.route', function () {
                    return new \Laminas\Diactoros\Response\HtmlResponse('<h1>Test Static Page</h1>');
                }),
            (new Sitemap())
                ->addStaticUrl('test.static.route')
                ->modifyResourceQuery(StaticUrls::class, fn (Collection $routes) => $routes->reject(fn (string $route) => $route === 'test.static.route'))
        );

        $urls = $this->getAllSitemapUrls();

        $this->assertEmpty(array_filter($urls, fn (string $url) => str_contains($url, '/test-static-page')), 'Filtered static route should not be in the sitemap');
        $this->assertEquals([1, 2, 3], $this->getIdsFromUrls($urls, '/d/'));
    }

    #[Test]
    public function modify_resource_query_returning_empty_collection_skips_resource()
    {
        $this->extend(
            (new Sitemap())
                ->modifyResourceQuery(StaticUrls::class, fn (Collection $routes) => collect())
        );

        $urls = $this->getAllSitemapUrls();

        $this->assertNotEmpty($urls);
        $this->assertEquals([1, 2, 3], $this->getIdsFromUrls($urls, '/d/'));
    }

    #[Test]
    public function modify_resource_query_leaves_other_resources_untouched()
    {
        $this->extend(
            (new Sitemap())
                ->modifyResourceQuery(\FoF\Sitemap\Resources\User::class, fn (Builder $query) => $query->whereRaw('1 = 0'))
        );

        $urls = $this->getAllSitemapUrls();

        $this->assertEmpty(array_filter($urls, fn (string $url) => preg_match('/\/u\/\w+/', $url)), 'User URLs should be filtered out');
        $this->assertEquals([1, 2, 3], $this->getIdsFromUrls($urls, '/d/'));
    }

    #[Test]
    public function modify_resource_query_validates_resource_inheritance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('has to extend');

        (new Sitemap())->modifyResourceQuery(\stdClass::class, fn ($query) => $query);
    }

    #[Test]
    public function modify_resource_query_rejects_invalid_return_value()
    {
        $this->extend(
            (new Sitemap())
                ->modifyResourceQuery(\FoF\Sitemap\Resources\Discussion::class, fn (Builder $query) => true)
        );

        $response = $this->send($this->request('GET', '/sitemap.xml'));

        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * Return the sorted, unique numeric IDs that follow $pathPrefix in the given URLs.
     */
    private function getIdsFromUrls(array $urls, string $pathPrefix): array
    {
        $ids = [];

        foreach ($urls as $url) {
            if (preg_match('#'.preg_quote($pathPrefix, '#').'(\d+)#', $url, $matches)) {
                $ids[] = (int) $matches[1];
            }
        }

        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }
}
