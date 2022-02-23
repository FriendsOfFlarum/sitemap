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
use FoF\Sitemap\Sitemap\Frequency;

class Home extends Disk
{
    /**
     * @var string
     */
    private $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    protected function chunk(): array
    {
        $fs = static::getTemporaryFilesystem();

        $path = 'sitemap-home.xml';

        $fs->put(
            $path,
            <<<'EOM'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
EOM
        );

        $fs->append(
            $path,
            $this->view()->make('fof-sitemap::url')->with('url', (object) [
                'location'        => $this->url,
                'lastModified'    => $now = Carbon::now(),
                'changeFrequency' => Frequency::DAILY,
                'priority'        => 0.9,
            ])->render()
        );

        $fs->append(
            $path,
            <<<'EOM'
</urlset>
EOM
        );

        $this->gzip($path);

        return [$path => $now];
    }
}
