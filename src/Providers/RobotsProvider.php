<?php

namespace FoF\Sitemap\Providers;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Deploy\DeployInterface;
use FoF\Sitemap\Generate\RobotsGenerator;
use FoF\Sitemap\Robots\Entries\AdminEntry;
use FoF\Sitemap\Robots\Entries\ApiEntry;
use FoF\Sitemap\Robots\Entries\AuthEntry;
use FoF\Sitemap\Robots\Entries\SitemapEntry;
use FoF\Sitemap\Robots\Entries\UserEntry;
use FoF\Sitemap\Robots\RobotsEntry;

/**
 * Service provider for robots.txt functionality.
 * 
 * Registers the robots.txt generator and default entries,
 * and sets up the necessary dependencies.
 */
class RobotsProvider extends AbstractServiceProvider
{
    /**
     * Register robots.txt services.
     */
    public function register(): void
    {
        // Register default robots.txt entries
        $this->container->bind('fof-sitemap.robots.entries', function () {
            return [
                AdminEntry::class,
                ApiEntry::class,
                AuthEntry::class,
                SitemapEntry::class,
                UserEntry::class,
            ];
        });

        // Register the robots generator
        $this->container->bind(RobotsGenerator::class, function ($container) {
            return new RobotsGenerator(
                $container->make(UrlGenerator::class),
                $container->make(DeployInterface::class),
                $container->make('fof-sitemap.robots.entries')
            );
        });
    }

    /**
     * Boot robots.txt services.
     */
    public function boot(): void
    {
        // Set static dependencies for RobotsEntry classes
        RobotsEntry::setUrlGenerator($this->container->make(UrlGenerator::class));
        RobotsEntry::setSettings($this->container->make(SettingsRepositoryInterface::class));
    }
}
