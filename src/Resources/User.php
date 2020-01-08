<?php

namespace FoF\Sitemap\Resources;

use FoF\Sitemap\Sitemap\Frequency;
use Flarum\User\User as Model;
use Flarum\User\Guest;
use Illuminate\Database\Eloquent\Builder;

class User extends Resource
{
    public function query(): Builder
    {
        return Model::whereVisibleTo(new Guest());
    }

    public function url($model): string
    {
        return $this->generateUrl("u/{$model->username}");
    }

    public function priority(): float
    {
        return 0.5;
    }

    public function frequency(): string
    {
        return Frequency::DAILY;
    }
}
