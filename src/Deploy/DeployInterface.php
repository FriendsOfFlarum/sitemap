<?php

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
