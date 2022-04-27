<?php

namespace FoF\Sitemap\Deploy;

interface DeployInterface
{
    public function storeSet($setIndex, string $set): ?StoredSet;
    public function storeIndex(string $index): ?string;
    public function getIndex(): ?string;
}
