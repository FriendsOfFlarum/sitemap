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

namespace FoF\Sitemap\Resources;

use Carbon\Carbon;
use Flarum\User\Guest;
use FoF\Pages\Page as Model;
use FoF\Sitemap\Sitemap\Frequency;
use Illuminate\Database\Eloquent\Builder;

class Page extends Resource
{
    public function query(): Builder
    {
        $query = Model::whereVisibleTo(new Guest());

        // If one of the pages is the homepage, it's already listed by the generator and we don't want to add it twice
        if (static::$settings->get('default_route') === '/pages/home') {
            $query->where('id', '!=', static::$settings->get('pages_home'));
        }

        return $query;
    }

    public function url($model): string
    {
        return $this->generateRouteUrl('pages.page', [
            'id' => $model->id.(trim($model->slug) ? '-'.$model->slug : ''),
        ]);
    }

    public function priority(): float
    {
        return 0.5;
    }

    public function frequency(): string
    {
        return Frequency::DAILY;
    }

    public function lastModifiedAt($model): Carbon
    {
        return $model->edit_time ?? $model->time;
    }

    public function enabled(): bool
    {
        return static::$extensionManager->isEnabled('fof-pages');
    }
}
