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
 * Robots.txt entry that disallows access to API endpoints.
 *
 * This entry prevents search engines from crawling the Flarum
 * API endpoints by dynamically determining the API path from
 * Flarum's configuration.
 */
class ApiEntry extends RobotsEntry
{
    /**
     * Get rules to disallow API paths.
     *
     * Dynamically determines the API path from Flarum's URL generator
     * to handle custom API path configurations.
     *
     * @return array Rules disallowing the configured API paths
     */
    public function getRules(): array
    {
        $apiPath = $this->getApiPath();

        if ($apiPath === null) {
            return [];
        }

        return $this->buildApiRules($apiPath);
    }

    /**
     * Get the API path from the URL generator.
     *
     * @return string|null The API path, or null if it can't be determined
     */
    protected function getApiPath(): ?string
    {
        try {
            $apiUrl = static::$urlGenerator->to('api')->base();
            $apiPath = parse_url($apiUrl, PHP_URL_PATH) ?: '/api';

            // Ensure path starts with /
            if (!str_starts_with($apiPath, '/')) {
                $apiPath = '/'.$apiPath;
            }

            return $apiPath;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Build the API disallow rules.
     *
     * @param string $apiPath The API path
     *
     * @return array Array of API disallow rules
     */
    protected function buildApiRules(string $apiPath): array
    {
        return [
            $this->disallowForAll($apiPath),
            $this->disallowForAll(rtrim($apiPath, '/').'/'),
        ];
    }
}
