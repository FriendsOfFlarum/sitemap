<?php

namespace FoF\Sitemap\Sitemap;

use Carbon\Carbon;
use FoF\Sitemap\Modes\ModeInterface;
use Illuminate\View\Factory;

class Sitemap
{
    public function __construct(
        public string $path,
        Carbon $lastModified
    ) {}

    public function toXML(Factory $view): string
    {
        /** @var ModeInterface $mode */
        $mode = resolve('fof-sitemap.mode');

        return $view->make('fof-sitemap::sitemap')->with('sitemap', $this)->render();
    }
}
