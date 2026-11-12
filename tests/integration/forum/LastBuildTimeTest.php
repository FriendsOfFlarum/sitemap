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
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\ConsoleTestCase;
use PHPUnit\Framework\Attributes\Test;

class LastBuildTimeTest extends ConsoleTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-sitemap');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Test Discussion', 'created_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_private' => 0],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(2023, 1, 1)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Test content</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function runtime_mode_requests_do_not_write_last_build_time()
    {
        $this->setting('fof-sitemap.mode', 'run');

        $this->database()->enableQueryLog();

        $writes = [];

        foreach (['/sitemap.xml', '/sitemap.xml', '/sitemap-0.xml'] as $path) {
            $response = $this->send($this->request('GET', $path));
            $this->assertEquals(200, $response->getStatusCode());

            $writes[$path][] = count($this->lastBuildTimeWrites());
            $this->database()->flushQueryLog();
        }

        $this->assertSame(
            ['/sitemap.xml' => [0, 0], '/sitemap-0.xml' => [0]],
            $writes,
            'Runtime-mode sitemap requests must not write fof-sitemap.last_build_time'
        );
        $this->assertNull($this->storedLastBuildTime());
    }

    #[Test]
    public function cached_mode_build_records_last_build_time()
    {
        $this->setting('fof-sitemap.mode', 'multi-file');

        $output = $this->runCommand(['command' => 'fof:sitemap:build']);

        $this->assertStringContainsString('Completed', $output);
        $this->assertNotNull($this->storedLastBuildTime());
    }

    private function lastBuildTimeWrites(): array
    {
        return array_values(array_filter(
            $this->database()->getQueryLog(),
            fn (array $entry) => preg_match('/^\s*(insert|update|replace)\b.*settings\b/is', $entry['query'])
                && in_array('fof-sitemap.last_build_time', $entry['bindings'], true)
        ));
    }

    private function storedLastBuildTime(): ?string
    {
        return $this->database()->table('settings')->where('key', 'fof-sitemap.last_build_time')->value('value');
    }
}
