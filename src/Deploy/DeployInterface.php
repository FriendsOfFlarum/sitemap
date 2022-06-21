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

namespace FoF\Sitemap\Deploy;

use Laminas\Diactoros\Uri;

interface DeployInterface
{
    public function storeSet($setIndex, string $set): ?StoredSet;

    public function storeIndex(string $index): ?string;

    /**
     * @return string|Uri|null
     */
    public function getIndex(): mixed;
}
