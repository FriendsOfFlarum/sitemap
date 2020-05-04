<?php

namespace FoF\Sitemap\Resources;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

abstract class Resource
{
    abstract public function url($model): string;

    abstract public function query(): Builder;

    abstract public function priority(): float;

    abstract public function frequency(): string;

    public function lastModifiedAt($model): Carbon
    {
        return Carbon::now();
    }

    protected function generateUrl($path): string
    {
        $url = app()->url();

        return "$url/$path";
    }
}
