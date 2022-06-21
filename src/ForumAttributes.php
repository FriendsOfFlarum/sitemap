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
use FoF\Sitemap\Resources\User;
use Illuminate\Contracts\Container\Container;

class ForumAttributes
{
    public function __invoke(ForumSerializer $serializer): array
    {
        // These values are only useful to admins since they are the only ones with access to the extension settings
        if (!$serializer->getActor()->isAdmin()) {
            return [];
        }

        return [
            // If the users index has been removed via the extender, we want to remove the related settings from the admin
            'fof-sitemap.usersIndexAvailable' => in_array(User::class, resolve('fof-sitemaps.resources')),
            // If the special extender to disable runtime has been used, we need this information to hide the matching settings
            'fof-sitemap.modeChoice' => !resolve(Container::class)->bound('fof-sitemaps.forceCached'),
        ];
    }
}
