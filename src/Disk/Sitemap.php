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
use Illuminate\Database\Eloquent\Builder;

class Sitemap extends Disk
{
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

    public function __construct(string $filename, Builder $query, callable $callback)
    {
        $this->filename = $filename;
        $this->query = $query;
        $this->callback = $callback;
    }

    public function each($item)
    {
        if ($callback = $this->callback) {
            $item = $callback($item);
        }

        return $item;
    }

    protected function chunk(): array
    {
        $index = 0;
        $filesWritten = [];

        $this->query->chunk(50000, function ($query) use (&$index, &$filesWritten) {
            $fs = static::getTemporaryFilesystem();

            $path = "sitemap-{$this->filename}-{$index}.xml";
            $lastModified = Carbon::now()->subYear();

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

            $this->gzip($path);

            $index++;

            $filesWritten[$path] = $lastModified;
        });

        return $filesWritten;
    }
}
