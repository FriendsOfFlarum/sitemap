<?php

namespace FoF\Sitemap\Robots\Entries;

use FoF\Sitemap\Robots\RobotsEntry;

/**
 * Robots.txt entry that disallows access to admin areas.
 * 
 * This entry prevents search engines from crawling the Flarum
 * admin panel by dynamically determining the admin path from
 * Flarum's configuration.
 */
class AdminEntry extends RobotsEntry
{
    /**
     * Get rules to disallow admin paths.
     * 
     * Dynamically determines the admin path from Flarum's URL generator
     * to handle custom admin path configurations.
     * 
     * @return array Rules disallowing the configured admin paths
     */
    public function getRules(): array
    {
        $adminPath = $this->getAdminPath();
        
        if ($adminPath === null) {
            return [];
        }

        return $this->buildAdminRules($adminPath);
    }

    /**
     * Get the admin path from the URL generator.
     * 
     * @return string|null The admin path, or null if it can't be determined
     */
    protected function getAdminPath(): ?string
    {
        try {
            $adminUrl = static::$urlGenerator->to('admin')->base();
            $adminPath = parse_url($adminUrl, PHP_URL_PATH) ?: '/admin';
            
            // Ensure path starts with /
            if (!str_starts_with($adminPath, '/')) {
                $adminPath = '/' . $adminPath;
            }

            return $adminPath;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Build the admin disallow rules.
     * 
     * @param string $adminPath The admin path
     * @return array Array of admin disallow rules
     */
    protected function buildAdminRules(string $adminPath): array
    {
        return [
            $this->disallowForAll($adminPath),
            $this->disallowForAll(rtrim($adminPath, '/') . '/')
        ];
    }
}
