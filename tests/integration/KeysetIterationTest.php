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

namespace FoF\Sitemap\Tests\integration;

use Flarum\Discussion\Discussion;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\TestCase;
use FoF\Sitemap\Deploy\Memory;
use FoF\Sitemap\Generate\Generator;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Test;

class KeysetIterationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->extension('fof-sitemap');
        $this->prepareDatabase([Discussion::class => array_map(fn ($id) => [
            'id'            => $id, 'title' => 'Discussion '.$id, 'slug' => 'discussion-'.$id,
            'created_at'    => '2026-01-01 00:00:00', 'last_posted_at' => '2026-01-01 00:00:00',
            'comment_count' => 1, 'is_private' => 0,
        ], [1, 3, 7, 9, 12, 15])]);
    }

    #[Test]
    public function filtered_discussions_cross_chunks_without_offsets(): void
    {
        $container = $this->app()->getContainer();
        $db = $container->make('db')->connection();
        $db->enableQueryLog();
        $deploy = $container->make(Memory::class);
        $calls = 0;
        $generator = new class($deploy, [KeysetResource::class], $container->make(SettingsRepositoryInterface::class), [[KeysetResource::class, function (Builder $query) use (&$calls) {
            $calls++;
            $query->where('id', '!=', 7);
        }],
        ]) extends Generator {
            protected function chunkSize(): int
            {
                return 2;
            }
        };
        $generator->generate();
        preg_match_all('~<loc>https://example.com/d/(\d+)</loc>~', $deploy->getSet(0), $matches);
        $this->assertSame(['1', '3', '9', '12', '15'], $matches[1]);
        $this->assertSame(1, $calls);
        $queries = array_values(array_filter($db->getQueryLog(), fn ($q) => str_contains($q['query'], 'select') && str_contains($q['query'], 'discussions')));
        $this->assertCount(3, $queries);
        $this->assertStringNotContainsString('offset', strtolower($queries[1]['query']));
        $this->assertStringContainsString('> ?', $queries[1]['query']);
    }

    #[Test]
    public function window_projection_keeps_full_result_totals_across_chunks(): void
    {
        $container = $this->app()->getContainer();
        $deploy = $container->make(Memory::class);
        $generator = new class($deploy, [WindowResource::class], $container->make(SettingsRepositoryInterface::class), [[WindowResource::class, fn (Builder $query) => $query->select('discussions.*')->selectRaw('COUNT(*) OVER () AS total')]]) extends Generator {
            protected function chunkSize(): int
            {
                return 2;
            }
        };
        $generator->generate();
        preg_match_all('~<loc>https://example.com/d/(\d+)/of-(\d+)</loc>~', $deploy->getSet(0), $matches);
        $this->assertSame(['1', '3', '7', '9', '12', '15'], $matches[1]);
        $this->assertSame(['6', '6', '6', '6', '6', '6'], $matches[2]);
    }

    #[Test]
    public function shipped_discussion_resource_keeps_keyset_iteration_with_risky_projection(): void
    {
        $this->setting('fof-sitemap.riskyPerformanceImprovements', true);
        $container = $this->app()->getContainer();
        $db = $container->make('db')->connection();
        $db->enableQueryLog();
        $deploy = $container->make(Memory::class);
        $generator = new class($deploy, [\FoF\Sitemap\Resources\Discussion::class], $container->make(SettingsRepositoryInterface::class)) extends Generator {
            protected function chunkSize(): int
            {
                return 2;
            }
        };
        $generator->generate();
        $this->assertSame(6, substr_count($deploy->getSet(0), '<loc>'));
        $queries = array_values(array_filter($db->getQueryLog(), fn ($q) => str_contains($q['query'], 'select') && str_contains($q['query'], 'discussions')));
        $this->assertCount(4, $queries);
        $this->assertStringNotContainsString('offset', strtolower($queries[1]['query']));
    }

    public static function customQueries(): array
    {
        return [['or'], ['projection'], ['union'], ['order'], ['limit'], ['collection'], ['scope-order'], ['scope-or'], ['scope-projection'], ['join'], ['alias']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('customQueries')]
    #[Test]
    public function custom_resource_query_contract_is_preserved(string $kind): void
    {
        $container = $this->app()->getContainer();
        $deploy = $container->make(Memory::class);
        $generator = new class($deploy, [KeysetResource::class], $container->make(SettingsRepositoryInterface::class), [[KeysetResource::class, function (Builder $query) use ($kind) {
            return match ($kind) {
                'or'               => $query->where('id', 1)->orWhere('id', '>=', 9),
                'projection'       => $query->select('slug'),
                'union'            => $query->where('id', '<', 7)->union(Discussion::where('id', '>=', 9))->orderBy('id'),
                'order'            => $query->orderByDesc('id'),
                'limit'            => $query->orderBy('id')->limit(3)->offset(1),
                'collection'       => $query->where('id', '<', 7)->get(),
                'scope-order'      => $query->withGlobalScope('order', fn (Builder $q) => $q->orderByDesc('id')),
                'scope-or'         => $query->withGlobalScope('or', fn (Builder $q) => $q->where('id', 1)->orWhere('id', '>=', 9)),
                'scope-projection' => $query->withGlobalScope('projection', fn (Builder $q) => $q->select('slug')),
                'join'             => $query->join('discussions as mirror', 'mirror.id', '=', 'discussions.id')->select('discussions.*'),
                'alias'            => $query->select('discussions.*')->selectRaw('id + 100 as id'),
            };
        }],
        ]) extends Generator {
            protected function chunkSize(): int
            {
                return 2;
            } };
        $generator->generate();
        preg_match_all('~<loc>https://example.com/d/(\d+)</loc>~', $deploy->getSet(0), $matches);
        $expected = match ($kind) {
            'or', 'scope-or' => ['1', '9', '12', '15'],
            'union'      => ['1', '3', '9', '12', '15'],
            'order'      => ['15', '12', '9', '7', '3', '1'],
            'limit'      => ['3', '7', '9'],
            'collection' => ['1', '3'],
            default      => ['1', '3', '7', '9', '12', '15'],
        };
        $this->assertSame($expected, $matches[1]);
    }
}
