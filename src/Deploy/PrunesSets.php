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

/**
 * Optional capability; third-party deployers retain their existing contract.
 * Implementers sharing persistent storage must serialize their builds themselves.
 * The generator provides a renewable cache lease for Disk and ProxyDisk subclasses.
 */
interface PrunesSets
{
    public function removeStaleSets(int $setCount): void;
}
