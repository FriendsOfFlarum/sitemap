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

namespace FoF\Sitemap\Tests\integration;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class ExcludeDiscussionQueryModifier
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function __invoke(Builder $query): void
    {
        $query->where('discussions.id', '!=', (int) $this->settings->get('fof-sitemap-test.excluded_discussion_id'));
    }
}
