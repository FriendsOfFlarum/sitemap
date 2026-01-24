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

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;

class ForumResourceFields
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Container $container
    ) {
    }

    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('fof-sitemap.usersIndexAvailable')
                ->visible(fn (\stdClass $model, Context $context) => $context->getActor()->isAdmin())
                ->get(function (\stdClass $model, Context $context) {
                    // If the users index has been removed via the extender, we want to remove the related settings from the admin
                    return in_array(Resources\User::class, resolve('fof-sitemaps.resources'));
                }),

            Schema\Boolean::make('fof-sitemap.modeChoice')
                ->visible(fn (\stdClass $model, Context $context) => $context->getActor()->isAdmin())
                ->get(function (\stdClass $model, Context $context) {
                    // If the special extender to disable runtime has been used, we need this information to hide the matching settings
                    return !$this->container->bound('fof-sitemaps.forceCached');
                }),

            Schema\Boolean::make('fof-sitemap.showBuildButton')
                ->visible(fn (\stdClass $model, Context $context) => $context->getActor()->isAdmin())
                ->get(function (\stdClass $model, Context $context) {
                    $mode = $this->settings->get('fof-sitemap.mode');
                    $isCachedMode = $mode !== 'run' || $this->container->bound('fof-sitemaps.forceCached');

                    // Show the build button when in cached mode (either via UI setting or forced via extender)
                    return $isCachedMode;
                }),
        ];
    }
}
