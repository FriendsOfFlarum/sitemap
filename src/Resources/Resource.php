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
use Flarum\Extension\ExtensionManager;
use Flarum\Http\SlugManager;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

abstract class Resource
{
    // Cached copies of the generator and slug manager for performance
    protected static ?UrlGenerator $generator = null;
    protected static ?SlugManager $slugManager = null;
    protected static ?SettingsRepositoryInterface $settings = null;
    protected static ?ExtensionManager $extensionManager = null;

    public static function setUrlGenerator(UrlGenerator $generator)
    {
        static::$generator = $generator;
    }

    public static function setSlugManager(SlugManager $slugManager)
    {
        static::$slugManager = $slugManager;
    }

    public static function setSettings(SettingsRepositoryInterface $settings)
    {
        static::$settings = $settings;
    }

    public static function setExtensionManager(ExtensionManager $extensionManager)
    {
        static::$extensionManager = $extensionManager;
    }

    abstract public function url($model): string;

    abstract public function query(): Builder|Collection;

    abstract public function priority(): float;

    abstract public function frequency(): string;

    public function lastModifiedAt($model): Carbon
    {
        return Carbon::now();
    }

    protected function generateRouteUrl(string $name, array $parameters = []): string
    {
        return static::$generator->to('forum')->route($name, $parameters);
    }

    protected function generateModelSlug(string $modelClass, AbstractModel $model): string
    {
        return static::$slugManager->forResource($modelClass)->toSlug($model);
    }

    public function enabled(): bool
    {
        return true;
    }
}
