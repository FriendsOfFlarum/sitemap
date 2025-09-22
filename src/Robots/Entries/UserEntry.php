<?php

namespace FoF\Sitemap\Robots\Entries;

use FoF\Sitemap\Robots\RobotsEntry;

/**
 * Robots.txt entry that conditionally disallows user pages.
 * 
 * This entry disallows user profile pages when the excludeUsers
 * setting is enabled, keeping robots.txt consistent with sitemap exclusions.
 */
class UserEntry extends RobotsEntry
{
    /**
     * Get rules to disallow user paths when users are excluded.
     * 
     * @return array Rules disallowing user-related paths if setting is enabled
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

        // Disallow user profile pages (/u/)
        $rules[] = $this->disallowForAll($forumPath . '/u/');

        return $rules;
    }

    /**
     * Check if user exclusion is enabled.
     * 
     * @return bool True if users should be excluded from robots.txt
     */
    public function enabled(): bool
    {
        return (bool) static::$settings->get('fof-sitemap.excludeUsers', false);
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
