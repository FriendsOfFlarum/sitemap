<?php

namespace FoF\Sitemap\Commands;

use Flarum\Foundation\Application;
use FoF\Sitemap\Disk\Index;
use Illuminate\Console\Command;

class MultiPageSitemapCommand extends Command
{
    protected $signature = 'fof:sitemap:multi';
    protected $description = 'Persists sitemap to disk into multiple gzipped files.';

    public function handle(Application $app)
    {
        $url = $app->url();

        $index = new Index(
            $url,
            $app->make('fof.sitemap.resources') ?? []
        );

        $index->write();

        $index->publish();
    }
}
