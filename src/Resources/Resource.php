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
    // Cached copies of the generator and slug manager for performance
    protected ?UrlGenerator $generator = null;
    protected ?SlugManager $slugManager = null;

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
        if (!$this->generator) {
            $this->generator = resolve(UrlGenerator::class);
        }

        return $this->generator->to('forum')->route($name, $parameters);
    }

    protected function generateModelSlug(string $modelClass, AbstractModel $model): string
    {
        if (!$this->slugManager) {
            $this->slugManager = resolve(SlugManager::class);
        }

        return $this->slugManager->forResource($modelClass)->toSlug($model);
    }

    public function enabled(): bool
    {
        return true;
    }
}
