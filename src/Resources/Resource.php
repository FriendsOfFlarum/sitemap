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
use Flarum\Database\AbstractModel;
use Flarum\Http\SlugManager;
use Flarum\Http\UrlGenerator;
use Illuminate\Database\Eloquent\Builder;

abstract class Resource
{
    public function maxId(): int
    {
        $model = $this->query()->getModel();

        return $model->withoutGlobalScopes()->max($model->getKeyName());
    }

    public function slug(): string
    {
        return $this->query()->getModel()->getTable();
    }

    abstract public function url($model): string;

    abstract public function query(): Builder;

    abstract public function priority(): float;

    abstract public function frequency(): string;

    public function lastModifiedAt($model): Carbon
    {
        return Carbon::now();
    }

    protected function generateRouteUrl(string $name, array $parameters = []): string
    {
        /** @var UrlGenerator $generator */
        $generator = resolve(UrlGenerator::class);

        return $generator->to('forum')->route($name, $parameters);
    }

    protected function generateModelSlug(string $modelClass, AbstractModel $model): string
    {
        /** @var SlugManager $slugManager */
        $slugManager = resolve(SlugManager::class);

        return $slugManager->forResource($modelClass)->toSlug($model);
    }

    public function enabled(): bool
    {
        return true;
    }
}
