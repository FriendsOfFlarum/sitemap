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

namespace FoF\Sitemap\Robots\Entries;

use FoF\Sitemap\Robots\RobotsEntry;

/**
 * Robots.txt entry that adds sitemap references.
 *
 * This entry adds sitemap URLs to the robots.txt file.
 * Can be extended to support multiple sitemaps for different languages,
 * regions, or content types.
 */
class SitemapEntry extends RobotsEntry
{
    /**
     * Get sitemap rules.
     *
     * Returns sitemap directives that should be added to robots.txt.
     * Note: Sitemap directives are global and don't belong to specific user-agents.
     *
     * @return array Array of sitemap rules
     */
    public function getRules(): array
    {
        return $this->buildSitemapRules();
    }

    /**
     * Build the sitemap rules.
     *
     * @return array Array of sitemap rules
     */
    protected function buildSitemapRules(): array
    {
        return [
            $this->sitemap($this->getSitemapUrl()),
        ];
    }

    /**
     * Get the sitemap URL.
     *
     * @return string The sitemap URL
     */
    protected function getSitemapUrl(): string
    {
        return $this->generateRouteUrl('fof-sitemap-index');
    }
}
