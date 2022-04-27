<?php

namespace FoF\Sitemap\Sitemap;

use Carbon\Carbon;
use Illuminate\View\Factory;

class Sitemap
{
    public function __construct(
        public array $sets,
        Carbon $lastModified
    ) {}

    public function toXML(): string
    {
        $view = resolve(Factory::class);
        return $view->make('fof-sitemap::sitemap')->with('sitemap', $this)->render();
    }
}
