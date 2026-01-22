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

namespace FoF\Sitemap;

use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Resources\User;
use Illuminate\Contracts\Container\Container;

class ForumAttributes
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Container $container
    ) {
    }

    public function __invoke(ForumSerializer $serializer): array
    {
        // These values are only useful to admins since they are the only ones with access to the extension settings
        if (!$serializer->getActor()->isAdmin()) {
            return [];
        }

        $mode = $this->settings->get('fof-sitemap.mode');
        $isCachedMode = $mode !== 'run' || $this->container->bound('fof-sitemaps.forceCached');

        return [
            // If the users index has been removed via the extender, we want to remove the related settings from the admin
            'fof-sitemap.usersIndexAvailable' => in_array(User::class, resolve('fof-sitemaps.resources')),
            // If the special extender to disable runtime has been used, we need this information to hide the matching settings
            'fof-sitemap.modeChoice' => !$this->container->bound('fof-sitemaps.forceCached'),
            // Show the build button when in cached mode (either via UI setting or forced via extender)
            'fof-sitemap.showBuildButton' => $isCachedMode,
        ];
    }
}
