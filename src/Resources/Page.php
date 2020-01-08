<?php

namespace FoF\Sitemap\Resources;

use Carbon\Carbon;
use FoF\Sitemap\Sitemap\Frequency;
use FoF\Pages\Page as Model;
use Illuminate\Database\Eloquent\Builder;

class Page extends Resource
{
    public function query(): Builder
    {
        return Model::query();
    }

    public function url($model): string
    {
        return $this->generateUrl("p/{$model->id}-{$model->slug}");
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
        return $model->edit_time;
    }
}
