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

use Illuminate\Database\Schema\Builder;

// This file needs to remain in order to maintain backwards compatibility when running down migrations.
// The default settings that were once added here are now handled by the default settings extender.
return [
    'up' => function (Builder $schema) {
        // do nothing
    },
    'down' => function (Builder $schema) {
        // do nothing
    },
];
