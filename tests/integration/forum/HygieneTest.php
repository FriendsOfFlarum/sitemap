<?php

namespace FoF\Sitemap\Tests\integration\forum;

use Flarum\Testing\integration\TestCase;
use FoF\Sitemap\Sitemap\UrlSet;
use PHPUnit\Framework\Attributes\Test;

class HygieneTest extends TestCase
{
    use \FoF\Sitemap\Tests\integration\XmlSitemapTestTrait;
    #[Test]
    public function homepage_ignores_activity_in_hidden_and_restricted_tags_in_one_query(): void
    {
        $this->extension('fof-sitemap');
        $this->extension('flarum-tags');
        $this->setting('default_route', '/all');
        $this->prepareDatabase([
            \Flarum\Discussion\Discussion::class => [
                ['id' => 1, 'title' => 'Listed', 'created_at' => '2026-01-01 00:00:00', 'last_posted_at' => '2026-02-01 00:00:00', 'is_private' => 0, 'comment_count' => 1],
                ['id' => 2, 'title' => 'Hidden tag', 'created_at' => '2026-01-01 00:00:00', 'last_posted_at' => '2026-03-01 00:00:00', 'is_private' => 0, 'comment_count' => 1],
                ['id' => 3, 'title' => 'Restricted', 'created_at' => '2026-01-01 00:00:00', 'last_posted_at' => '2026-04-01 00:00:00', 'is_private' => 0, 'comment_count' => 1],
            ],
            \Flarum\Tags\Tag::class => [
                ['id' => 1, 'name' => 'Listed', 'slug' => 'listed', 'is_hidden' => 0],
                ['id' => 2, 'name' => 'Hidden', 'slug' => 'hidden', 'is_hidden' => 1],
                ['id' => 3, 'name' => 'Restricted', 'slug' => 'restricted', 'is_restricted' => 1],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 2],
                ['discussion_id' => 3, 'tag_id' => 3],
            ],
        ]);
        $db = $this->app()->getContainer()->make('db')->connection();
        // Warm permission/visibility metadata before measuring the activity query.
        \Flarum\Discussion\Discussion::whereVisibleTo(new \Flarum\User\Guest())->first();
        $db->enableQueryLog();
        $resource = new \FoF\Sitemap\Resources\StaticUrls();
        $this->assertSame('2026-02-01', $resource->lastModifiedAt('index')?->toDateString());
        $this->assertCount(1, $db->getQueryLog());
    }

    #[Test]
    public function empty_and_custom_homepages_omit_lastmod(): void
    {
        $this->extension('fof-sitemap');
        $this->setting('default_route', '/all');
        $container = $this->app()->getContainer();
        $resource = new \FoF\Sitemap\Resources\StaticUrls();
        $this->assertNull($resource->lastModifiedAt('index'));
        $container->make(\Flarum\Settings\SettingsRepositoryInterface::class)->set('default_route', '/pages/home');
        $this->assertNull($resource->lastModifiedAt('index'));
        $this->assertNull($resource->lastModifiedAt('tags'));
        $this->assertNull($resource->lastModifiedAt('third-party.route'));
    }

    #[Test]
    public function served_xml_uses_tag_activity_and_omits_user_lastmod(): void
    {
        $this->extension('fof-sitemap');
        $this->extension('flarum-tags');
        $this->prepareDatabase([
            \Flarum\Tags\Tag::class => [
                ['id' => 1, 'name' => 'Active', 'slug' => 'active', 'discussion_count' => 10, 'last_posted_at' => '2026-02-01 00:00:00'],
                ['id' => 2, 'name' => 'Unknown activity', 'slug' => 'unknown', 'discussion_count' => 10, 'last_posted_at' => null],
            ],
            \Flarum\User\User::class => [
                ['id' => 2, 'username' => 'member', 'email' => 'member@example.com', 'joined_at' => '2026-01-01 00:00:00', 'comment_count' => 10],
            ],
        ]);
        $index = $this->send($this->request('GET', '/sitemap.xml'));
        $this->assertSame(200, $index->getStatusCode());
        $found = [];
        foreach ($this->getSitemapUrls((string) $index->getBody()) as $url) {
            $response = $this->send($this->request('GET', parse_url($url, PHP_URL_PATH)));
            $this->assertSame(200, $response->getStatusCode());
            $xpath = $this->parseXmlWithNamespace((string) $response->getBody());
            foreach ($xpath->query('//sm:url') as $node) {
                $location = $xpath->evaluate('string(sm:loc)', $node);
                if (str_ends_with($location, '/t/active')) {
                    $this->assertSame('2026-02-01T00:00:00+00:00', $xpath->evaluate('string(sm:lastmod)', $node));
                    $found[] = 'active';
                } elseif (str_ends_with($location, '/t/unknown') || str_contains($location, '/u/')) {
                    $this->assertSame(0, $xpath->query('sm:lastmod', $node)->length);
                    $found[] = str_contains($location, '/u/') ? 'user' : 'unknown';
                }
            }
        }
        sort($found);
        $this->assertSame(['active', 'unknown', 'user'], $found);
    }

    #[Test]
    public function homepage_uses_latest_visible_discussion_activity(): void
    {
        $this->extension('fof-sitemap');
        $this->setting('default_route', '/all');
        $this->prepareDatabase([\Flarum\Discussion\Discussion::class => [
            ['id' => 1, 'title' => 'Public', 'created_at' => '2026-01-01 00:00:00', 'last_posted_at' => '2026-02-01 00:00:00', 'is_private' => 0],
            ['id' => 2, 'title' => 'Hidden', 'created_at' => '2026-01-01 00:00:00', 'last_posted_at' => '2026-03-01 00:00:00', 'hidden_at' => '2026-03-01 00:00:00', 'is_private' => 0],
        ]]);
        $this->app();
        $resource = new \FoF\Sitemap\Resources\StaticUrls();
        $this->assertSame('2026-02-01', $resource->lastModifiedAt('index')?->toDateString());
    }

    #[Test]
    public function new_install_omits_optional_hints_and_users_have_no_lastmod(): void
    {
        $this->extension('fof-sitemap');
        $container = $this->app()->getContainer();
        $set = new UrlSet();
        $set->addUrl('https://example.com/', null, 'daily', 0.5);
        $this->assertStringNotContainsString('<priority>', $set->toXml());
        $this->assertStringNotContainsString('<changefreq>', $set->toXml());
        $this->assertNull((new \FoF\Sitemap\Resources\User())->lastModifiedAt(null));
        $this->assertNull((new \FoF\Sitemap\Resources\StaticUrls())->lastModifiedAt('tags'));
    }
}
