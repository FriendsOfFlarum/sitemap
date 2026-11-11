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

class WindowResource extends KeysetResource
{
    public function url($model): string { return parent::url($model).'/of-'.$model->total; }
}
