<?php

namespace FoF\Sitemap\Tests\integration;

use Carbon\Carbon;
use FoF\Sitemap\Resources\Discussion;
use FoF\Sitemap\Sitemap\Frequency;

class TestDiscussionResource extends Discussion
{
    public function url($model): string
    {
        // Use a custom URL pattern to distinguish from the original
        return '/custom-discussion/' . $model->id . '-' . $model->slug;
    }

    public function priority(): float
    {
        // Higher priority than the default Discussion resource (0.9)
        return 1.0;
    }

    public function frequency(): string
    {
        // Different frequency than the default
        return Frequency::HOURLY;
    }

    public function lastModifiedAt($model): Carbon
    {
        // Same as parent but we can customize if needed
        return parent::lastModifiedAt($model);
    }
}
