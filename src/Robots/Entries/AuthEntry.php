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
 * Robots.txt entry that disallows access to authentication pages.
 *
 * This entry prevents search engines from crawling login, registration,
 * and other authentication-related pages that provide no SEO value.
 */
class AuthEntry extends RobotsEntry
{
    /**
     * Get rules to disallow authentication paths.
     *
     * Uses actual Flarum route names to generate the correct paths,
     * ensuring compatibility with any custom route configurations.
     * Only includes rules for routes that actually exist.
     *
     * @return array Rules disallowing authentication-related paths
     */
    public function getRules(): array
    {
        $rules = [];

        // Settings and notifications require login
        if ($path = $this->getRoutePath('settings')) {
            $rules[] = $this->disallowForAll($path);
        }
        if ($path = $this->getRoutePath('notifications')) {
            $rules[] = $this->disallowForAll($path);
        }

        // Logout functionality
        if ($path = $this->getRoutePath('logout')) {
            $rules[] = $this->disallowForAll($path);
        }

        // Password reset paths - use base path since tokens are dynamic
        if ($path = $this->getRouteBasePath('resetPassword')) {
            $rules[] = $this->disallowForAll($path);
        }

        // Email confirmation paths - use base path since tokens are dynamic
        if ($path = $this->getRouteBasePath('confirmEmail')) {
            $rules[] = $this->disallowForAll($path);
        }

        return $rules;
    }

    /**
     * Get the path for a route name.
     *
     * @param string $routeName The route name
     *
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
     * Get the base path for routes with parameters (like tokens).
     *
     * @param string $routeName The route name
     *
     * @return string|null The base route path without parameters, or null if route doesn't exist
     */
    protected function getRouteBasePath(string $routeName): ?string
    {
        // For routes with parameters, we need to extract just the base path
        // /reset/{token} becomes /reset
        // /confirm/{token} becomes /confirm

        if ($routeName === 'resetPassword') {
            $forumPath = $this->getForumBasePath();

            return $forumPath !== null ? $forumPath.'/reset' : null;
        }

        if ($routeName === 'confirmEmail') {
            $forumPath = $this->getForumBasePath();

            return $forumPath !== null ? $forumPath.'/confirm' : null;
        }

        return $this->getRoutePath($routeName);
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
