<?php

namespace FoF\Sitemap\Generate;

use Flarum\Http\UrlGenerator;
use FoF\Sitemap\Deploy\DeployInterface;

/**
 * Generates robots.txt content from registered entries.
 * 
 * This class collects all registered robots.txt entries and generates
 * a standards-compliant robots.txt file. It groups rules by user-agent
 * and automatically includes sitemap references.
 * 
 * @example
 * $generator = resolve(RobotsGenerator::class);
 * $robotsContent = $generator->generate();
 */
class RobotsGenerator
{
    /**
     * @param UrlGenerator $url URL generator for creating sitemap references
     * @param DeployInterface $deploy Deployment interface for consistency with sitemap system
     * @param array $entries Array of registered RobotsEntry class names
     */
    public function __construct(
        protected UrlGenerator $url,
        protected DeployInterface $deploy,
        protected array $entries = []
    ) {}

    /**
     * Generate the complete robots.txt content.
     * 
     * Processes all registered entries, groups rules by user-agent,
     * and formats them according to robots.txt standards.
     * Sitemap URLs are handled as separate global directives.
     * 
     * @return string Complete robots.txt content
     */
    public function generate(): string
    {
        $content = [];
        $sitemapRules = [];
        
        // Group entries by user-agent and collect sitemap rules
        $userAgentGroups = [];
        
        foreach ($this->entries as $entryClass) {
            $entry = resolve($entryClass);
            if ($entry->enabled()) {
                $rules = $entry->getRules();
                foreach ($rules as $rule) {
                    // Handle sitemap rules separately
                    if (isset($rule['sitemap'])) {
                        $sitemapRules[] = $rule['sitemap'];
                        continue;
                    }
                    
                    $userAgent = $rule['user_agent'] ?? '*';
                    if (!isset($userAgentGroups[$userAgent])) {
                        $userAgentGroups[$userAgent] = [];
                    }
                    $userAgentGroups[$userAgent][] = $rule;
                }
            }
        }

        // Generate robots.txt content for user-agent rules
        foreach ($userAgentGroups as $userAgent => $rules) {
            $content[] = "User-agent: {$userAgent}";
            
            foreach ($rules as $rule) {
                if (isset($rule['disallow'])) {
                    $content[] = "Disallow: {$rule['disallow']}";
                }
                if (isset($rule['allow'])) {
                    $content[] = "Allow: {$rule['allow']}";
                }
                if (isset($rule['crawl_delay'])) {
                    $content[] = "Crawl-delay: {$rule['crawl_delay']}";
                }
            }
            $content[] = ''; // Empty line between user-agent groups
        }

        // Add sitemap references at the end
        foreach ($sitemapRules as $sitemapUrl) {
            $content[] = "Sitemap: {$sitemapUrl}";
        }

        return implode("\n", $content);
    }
}
