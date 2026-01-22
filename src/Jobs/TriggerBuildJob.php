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
use FoF\Sitemap\Generate\Generator;
use Psr\Log\LoggerInterface;

class TriggerBuildJob extends AbstractJob
{
    public function handle(): void
    {
        $logger = resolve(LoggerInterface::class);
        $logger->info('[FoF Sitemap] TriggerBuildJob.handle() called');

        /** @var Generator $generator */
        $generator = resolve(Generator::class);
        $logger->info('[FoF Sitemap] Generator resolved: '.get_class($generator));

        $generator->generate();

        $logger->info('[FoF Sitemap] Generator.generate() completed');
    }
}
