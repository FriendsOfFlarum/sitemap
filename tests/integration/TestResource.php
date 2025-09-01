<?php

namespace FoF\Sitemap\Tests\integration;

use Carbon\Carbon;
use Flarum\User\User;
use FoF\Sitemap\Resources\Resource;
use FoF\Sitemap\Sitemap\Frequency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TestResource extends Resource
{
    public function query(): Builder|Collection
    {
        // Return a query builder for existing users (which are AbstractModel instances)
        // This ensures we return proper Eloquent models that the Generator expects
        return User::query()->limit(2);
    }

    public function url($model): string
    {
        // $model will be a User instance, so we can access its properties
        return '/test-resource/user-' . $model->id;
    }

    public function priority(): float
    {
        return 0.8;
    }

    public function frequency(): string
    {
        return Frequency::WEEKLY;
    }

    public function lastModifiedAt($model): Carbon
    {
        // $model is a User, so use joined_at
        return $model->joined_at ?? Carbon::now();
    }

    public function enabled(): bool
    {
        return true;
    }
}
