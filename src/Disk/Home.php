<?php

/*
 * This file is part of fof/sitemap.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 *  For the full copyright and license information, please view the LICENSE.md
 *  file that was distributed with this source code.
 *
 */

namespace FoF\Sitemap\Disk;

use Carbon\Carbon;
use FoF\Sitemap\Sitemap\Frequency;

class Home extends Sitemap
{
    /**
     * @var string
     */
    private $url;

    public function __construct(string $url, string $tmpDir = null)
    {
        $this->tmpDir = $tmpDir;
        $this->url = $url;
    }

    protected function chunk(string $directory): array
    {
        $filename = 'sitemap-home.xml';

        $stream = fopen($path = "$directory/$filename", 'w+');

        fwrite(
            $stream,
            <<<'EOM'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
EOM
        );

        fwrite(
            $stream,
            $this->view()->make('fof-sitemap::url')->with('url', (object) [
                'location'        => $this->url,
                'lastModified'    => $now = Carbon::now(),
                'changeFrequency' => Frequency::DAILY,
                'priority'        => 0.9,
            ])->render()
        );

        fwrite(
            $stream,
            <<<'EOM'
</urlset>
EOM
        );

        fclose($stream);

        if ($gzipped = $this->gzCompressFile($path)) {
            unlink($path);
        }

        $path = str_replace($directory, null, $gzipped ?? $path);

        return [$path => $now];
    }
}
