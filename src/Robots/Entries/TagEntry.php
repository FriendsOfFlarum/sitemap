<?php

namespace FoF\Sitemap\Robots\Entries;

use FoF\Sitemap\Robots\RobotsEntry;

/**
 * Robots.txt entry that conditionally disallows tag pages.
 * 
 * This entry disallows tag pages when the excludeTags setting is enabled,
 * keeping robots.txt consistent with sitemap exclusions.
 */
class TagEntry extends RobotsEntry
{
    /**
     * Get rules to disallow tag paths when tags are excluded.
     * 
     * @return array Rules disallowing tag-related paths if setting is enabled
     */
    public function getRules(): array
    {
        if (!$this->enabled()) {
            return [];
        }

        $rules = [];
        
        // Get the forum base path
        $forumPath = $this->getForumBasePath();
        if ($forumPath === null) {
            return [];
        }

        // Disallow individual tag pages (/t/)
        $rules[] = $this->disallowForAll($forumPath . '/t/');
        
        // Disallow tags index page (/tags)
        if ($tagsPath = $this->getRoutePath('tags')) {
            $rules[] = $this->disallowForAll($tagsPath);
        }

        return $rules;
    }

    /**
     * Check if tag exclusion is enabled.
     * 
     * @return bool True if tags should be excluded from robots.txt
     */
    public function enabled(): bool
    {
        return (bool) static::$settings->get('fof-sitemap.excludeTags', false);
    }

    /**
     * Get the path for a route name.
     * 
     * @param string $routeName The route name
     * @return string|null The route path, or null if route doesn't exist
     */
    protected function getRoutePath(string $routeName): ?string
    {
        try {
            $url = $this->generateRouteUrl($routeName);
            return parse_url($url, PHP_URL_PATH) ?: null;
        } catch (\Exception $e) {
            // Route doesn't exist, return null to exclude it
            return null;
        }
    }

    /**
     * Get the forum base path.
     * 
     * @return string|null The forum base path, or null if it can't be determined
     */
    protected function getForumBasePath(): ?string
    {
        try {
            $forumUrl = static::$urlGenerator->to('forum')->base();
            $path = parse_url($forumUrl, PHP_URL_PATH);
            return $path !== false ? rtrim($path ?: '', '/') : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
