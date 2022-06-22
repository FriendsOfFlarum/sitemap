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

use Carbon\Carbon;
use Laminas\Diactoros\Uri;

interface DeployInterface
{
    public function storeSet($setIndex, string $set): ?StoredSet;

    public function storeIndex(string $index): ?string;

    /**
     * @return string|Uri|null
     */
    public function getIndex(): mixed;

    /**
     * @return string|Uri|null
     */
    public function getSet($setIndex): mixed;

    public function indexIsStale(Carbon $mustBeNewerThan): bool;

    public function setIsStale($setIndex, Carbon $mustBeNewerThan): bool;
}
