<?php

namespace FoF\Sitemap\Deploy;

use FoF\Sitemap\Sitemap\UrlSet;

interface DeployInterface
{
    /**
     * Stores the sitemap.
     *
     * @param UrlSet $set
     * @return string|null
     */
    public function store(UrlSet $set): ?string;

    /**
     * The base URL where files are remotely stored. Leave null for local.
     *
     * @return string|null
     */
    public function url(): ?string;
}
