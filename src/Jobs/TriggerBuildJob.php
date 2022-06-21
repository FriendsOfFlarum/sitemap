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

namespace FoF\Sitemap\Jobs;

use Flarum\Queue\AbstractJob;
use FoF\Sitemap\Console\BuildSitemapCommand;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class TriggerBuildJob extends AbstractJob
{
    public function handle(): void
    {
        /** @var Container $container */
        $container = resolve(Container::class);

        /** @var BuildSitemapCommand $command */
        $command = resolve(BuildSitemapCommand::class);

        $command->setLaravel($container);

        $command->run(new ArrayInput([]), new NullOutput());
    }
}
