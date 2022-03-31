<?php

namespace FoF\Sitemap\Sitemap;

use Carbon\Carbon;
use FoF\Sitemap\Deploy\DeployInterface;
use Illuminate\View\Factory;

class Sitemap
{
    public function __construct(
        public string $path,
        Carbon $lastModified
    ) {}

    public function toXML(Factory $view): string
    {
        /** @var DeployInterface $mode */
        $mode = resolve('fof-sitemap.mode');

        return $view->make('fof-sitemap::sitemap')->with('sitemap', $this)->render();
    }
}
