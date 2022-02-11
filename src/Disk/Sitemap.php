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

namespace FoF\Sitemap\Disk;

use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;

class Sitemap
{
    protected static Filesystem $temporaryFilesystem;
    /**
     * @var string
     */
    protected $filename;
    /**
     * @var Builder
     */
    protected $query;
    /**
     * @var callable
     */
    protected $callback;
    /**
     * @var string
     */
    protected $tmpDir;

    public function __construct(string $filename, Builder $query, callable $callback, string $tmpDir = null)
    {
        $this->filename = $filename;
        $this->query = $query;
        $this->callback = $callback;
        $this->tmpDir = $tmpDir;
    }

    /**
     * Limit the number of entries to 50.000.
     *
     * @return array|string[]
     */
    public function write(): array
    {
        return $this->chunk();
    }

    public function each($item)
    {
        if ($callback = $this->callback) {
            $item = $callback($item);
        }

        return $item;
    }

    protected function view(): Factory
    {
        return resolve(Factory::class);
    }

    protected function chunk(): array
    {
        $index = 0;
        $filesWritten = [];

        $this->query->chunk(50000, function ($query) use (&$index, &$filesWritten) {
            $fs = static::$temporaryFilesystem;

            $filename = "sitemap-{$this->filename}-{$index}.xml";
            $lastModified = Carbon::now()->subYear();
            $path = "sitemaps/$filename";

            $fs->put(
                $path,
                <<<'EOM'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
EOM
            );

            $query->each(function ($item) use (&$lastModified, $fs, $path) {
                $url = $this->each($item);

                if ($url->lastModified->isAfter($lastModified)) {
                    $lastModified = $url->lastModified;
                }

                $fs->append(
                    $path,
                    $this->view()->make('fof-sitemap::url')->with('url', $url)->render()
                );
            });

            $fs->append(
                $path,
                <<<'EOM'
</urlset>
EOM
            );



            $index++;

            $filesWritten[$path] = $lastModified;
        });

        return $filesWritten;
    }

    protected function gzip(string $path)
    {
        $fs = static::$temporaryFilesystem;
        
        // Check gzip
        if (function_exists('gzencode')) {
            $fs->put(
                $path,
                gzencode($fs->get($path))
            );
        }
    }

    public static function setTemporaryFilesystem(Filesystem $filesystem): void
    {
        static::$temporaryFilesystem = $filesystem;
    }
}
