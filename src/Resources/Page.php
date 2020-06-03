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
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Guest;
use FoF\Pages\Page as Model;
use FoF\Sitemap\Sitemap\Frequency;
use Illuminate\Database\Eloquent\Builder;

class Page extends Resource
{
    public function query(): Builder
    {
        // In pre-0.4.0 versions of fof/pages, ScopeVisibilityTrait was not used
        // If such an older version is installed, we don't want to list any page by risk of listing drafts and private pages
        if (!class_uses(Model::class, ScopeVisibilityTrait::class)) {
            return Model::whereRaw('0=1');
        }

        $query = Model::whereVisibleTo(new Guest());

        /** @var SettingsRepositoryInterface $settings */
        $settings = app(SettingsRepositoryInterface::class);

        // If one of the pages is the homepage, it's already listed by the generator and we don't want to add it twice
        if ($settings->get('default_route') === '/pages/home') {
            $query->where('id', '!=', $settings->get('pages_home'));
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
}
