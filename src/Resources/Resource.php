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

namespace FoF\Sitemap\Resources;

use Carbon\Carbon;
use Flarum\Foundation\Config;
use Flarum\Http\UrlGenerator;
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

    /**
     * Generates an absolute URL to an arbitrary path
     * Not actually used by the extension anymore but kept for compatibility with third-party code extending this class.
     *
     * @param $path
     *
     * @return string
     */
    protected function generateUrl($path): string
    {
        $url = app(Config::class)->url();

        return "$url/$path";
    }

    /**
     * Generates an absolute URL to a named route.
     *
     * @param $name
     * @param array $parameters
     *
     * @return string
     */
    protected function generateRouteUrl($name, $parameters = []): string
    {
        /**
         * @var $generator UrlGenerator
         */
        $generator = app(UrlGenerator::class);

        return $generator->to('forum')->route($name, $parameters);
    }
}
