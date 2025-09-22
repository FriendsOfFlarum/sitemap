<?php

namespace FoF\Sitemap\Robots;

use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Abstract base class for robots.txt entries.
 * 
 * Extend this class to create custom robots.txt entries that can be
 * registered through the Robots extender. Each entry can define
 * multiple rules for different user agents.
 * 
 * @example
 * class MyCustomEntry extends RobotsEntry
 * {
 *     public function getRules(): array
 *     {
 *         return [
 *             [
 *                 'user_agent' => '*',
 *                 'disallow' => '/private'
 *             ],
 *             [
 *                 'user_agent' => 'Googlebot',
 *                 'crawl_delay' => 10
 *             ]
 *         ];
 *     }
 * }
 */
abstract class RobotsEntry
{
    protected static UrlGenerator $urlGenerator;
    protected static SettingsRepositoryInterface $settings;

    /**
     * Set the URL generator instance.
     * 
     * @param UrlGenerator $generator The URL generator instance
     */
    public static function setUrlGenerator(UrlGenerator $generator): void
    {
        static::$urlGenerator = $generator;
    }

    /**
     * Set the settings repository instance.
     * 
     * @param SettingsRepositoryInterface $settings The settings repository instance
     */
    public static function setSettings(SettingsRepositoryInterface $settings): void
    {
        static::$settings = $settings;
    }

    /**
     * Get robots.txt rules for this entry.
     * 
     * Return an array of rules where each rule is an associative array
     * that can contain the following keys:
     * - 'user_agent': The user agent this rule applies to (defaults to '*')
     * - 'disallow': Path to disallow for this user agent
     * - 'allow': Path to allow for this user agent
     * - 'crawl_delay': Crawl delay in seconds for this user agent
     * - 'sitemap': Sitemap URL (global directive, not user-agent specific)
     * 
     * @return array Array of rules with keys: user_agent, disallow, allow, crawl_delay
     * 
     * @example
     * return [
     *     ['user_agent' => '*', 'disallow' => '/admin'],
     *     ['user_agent' => 'Googlebot', 'crawl_delay' => 10],
     *     ['user_agent' => '*', 'allow' => '/public']
     * ];
     */
    abstract public function getRules(): array;

    /**
     * Whether this entry is enabled.
     * 
     * Override this method to conditionally enable/disable the entry
     * based on settings, extension status, or other conditions.
     * 
     * @return bool True if the entry should be included in robots.txt
     */
    public function enabled(): bool
    {
        return true;
    }

    /**
     * Generate a URL for a named route.
     * 
     * Helper method to generate URLs for Flarum routes.
     * 
     * @param string $name Route name
     * @param array $parameters Route parameters
     * @return string Generated URL
     */
    protected function generateRouteUrl(string $name, array $parameters = []): string
    {
        return static::$urlGenerator->to('forum')->route($name, $parameters);
    }

    /**
     * Create a disallow rule for all user agents.
     * 
     * @param string $path Path to disallow
     * @return array Disallow rule for all user agents
     */
    protected function disallowForAll(string $path): array
    {
        return ['user_agent' => '*', 'disallow' => $path];
    }

    /**
     * Create a disallow rule for a specific user agent.
     * 
     * @param string $userAgent User agent name
     * @param string $path Path to disallow
     * @return array Disallow rule for specific user agent
     */
    protected function disallowFor(string $userAgent, string $path): array
    {
        return ['user_agent' => $userAgent, 'disallow' => $path];
    }

    /**
     * Create an allow rule for all user agents.
     * 
     * @param string $path Path to allow
     * @return array Allow rule for all user agents
     */
    protected function allowForAll(string $path): array
    {
        return ['user_agent' => '*', 'allow' => $path];
    }

    /**
     * Create an allow rule for a specific user agent.
     * 
     * @param string $userAgent User agent name
     * @param string $path Path to allow
     * @return array Allow rule for specific user agent
     */
    protected function allowFor(string $userAgent, string $path): array
    {
        return ['user_agent' => $userAgent, 'allow' => $path];
    }

    /**
     * Create a crawl delay rule for all user agents.
     * 
     * @param int $seconds Crawl delay in seconds
     * @return array Crawl delay rule for all user agents
     */
    protected function crawlDelayForAll(int $seconds): array
    {
        return ['user_agent' => '*', 'crawl_delay' => $seconds];
    }

    /**
     * Create a crawl delay rule for a specific user agent.
     * 
     * @param string $userAgent User agent name
     * @param int $seconds Crawl delay in seconds
     * @return array Crawl delay rule for specific user agent
     */
    protected function crawlDelayFor(string $userAgent, int $seconds): array
    {
        return ['user_agent' => $userAgent, 'crawl_delay' => $seconds];
    }

    /**
     * Create a sitemap rule.
     * 
     * @param string $url Sitemap URL
     * @return array Sitemap rule
     */
    protected function sitemap(string $url): array
    {
        return ['sitemap' => $url];
    }
}
