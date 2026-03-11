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
    /**
     * Store a sitemap URL set from a stream resource.
     *
     * The stream is positioned at the start and should be read to completion.
     * Implementations must NOT close the stream; the caller owns it.
     *
     * @param int      $setIndex Zero-based index of the sitemap set
     * @param resource $stream   Readable stream containing the XML content
     */
    public function storeSet(int $setIndex, $stream): ?StoredSet;

    public function storeIndex(string $index): ?string;

    /**
     * @return string|Uri|null
     */
    public function getIndex(): mixed;

    public function getSet($setIndex): ?string;
}
