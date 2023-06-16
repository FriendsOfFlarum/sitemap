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
use Flarum\Discussion\Discussion as Model;
use Flarum\User\Guest;
use FoF\Sitemap\Sitemap\Frequency;
use Illuminate\Database\Eloquent\Builder;

class Discussion extends Resource
{
    public function query(): Builder
    {
        $query = Model::whereVisibleTo(new Guest());

        if (static::$settings->get('fof-sitemap.riskyPerformanceImprovements')) {
            // Limiting the number of columns to fetch improves query time
            // This is a risky optimization because of 2 reasons:
            // A custom slug driver might need a column not included in this list
            // A custom visibility scope might depend on a column or alias being part of the SELECT statement
            $query->select([
                'id',
                'slug',
                'created_at',
                'last_posted_at',
            ]);
        }

        return $query;
    }

    public function url($model): string
    {
        return $this->generateRouteUrl('discussion', [
            'id' => $this->generateModelSlug(Model::class, $model),
        ]);
    }

    public function priority(): float
    {
        return 0.9;
    }

    public function frequency(): string
    {
        return Frequency::DAILY;
    }

    public function lastModifiedAt($model): Carbon
    {
        return $model->last_posted_at ?? $model->created_at;
    }
}
