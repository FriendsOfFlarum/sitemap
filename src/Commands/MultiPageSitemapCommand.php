<?php

namespace Flagrow\Sitemap\Commands;

use Flagrow\Sitemap\Disk\Index;
use Flarum\Foundation\Application;
use Illuminate\Console\Command;

class MultiPageSitemapCommand extends Command
{
    protected $signature = 'flagrow:sitemap:multi-page';
    protected $description = 'Persists sitemap to disk into multiple gzipped files.';

    public function handle(Application $app)
    {
        $url = $app->url();

        $index = new Index(
            $url,
            $app->make('flagrow.sitemap.resources') ?? []
        );

        $index->write();

        $index->publish();
    }
}
