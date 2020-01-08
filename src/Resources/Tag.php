<?php

namespace FoF\Sitemap\Resources;

use FoF\Sitemap\Sitemap\Frequency;
use Flarum\Tags\Tag as Model;
use Flarum\User\Guest;
use Illuminate\Database\Eloquent\Builder;

class Tag extends Resource
{
    public function query(): Builder
    {
        return Model::whereVisibleTo(new Guest());
    }

    public function url($model): string
    {
        return $this->generateUrl("t/{$model->slug}");
    }

    public function priority(): float
    {
        return 0.9;
    }

    public function frequency(): string
    {
        return Frequency::DAILY;
    }
}
