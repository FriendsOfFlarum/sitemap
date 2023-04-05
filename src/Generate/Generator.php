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

namespace FoF\Sitemap\Generate;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Flarum\Database\AbstractModel;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Deploy\DeployInterface;
use FoF\Sitemap\Deploy\StoredSet;
use FoF\Sitemap\Exceptions\SetLimitReachedException;
use FoF\Sitemap\Resources\Resource as AbstractResource;
use FoF\Sitemap\Sitemap\Sitemap;
use FoF\Sitemap\Sitemap\Url;
use FoF\Sitemap\Sitemap\UrlSet;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Generator
{
    public function __construct(
        protected DeployInterface $deploy,
        protected array $resources
    ) {
    }

    public function generate(OutputInterface $output = null): ?string
    {
        if (!$output) {
            $output = new NullOutput();
        }

        $startTime = Carbon::now();

        $now = Carbon::now();

        $url = $this->deploy->storeIndex(
            (new Sitemap($this->loop($output), $now))->toXML()
        );

        $output->writeln('Completed in '.$startTime->diffForHumans(null, CarbonInterface::DIFF_ABSOLUTE, true, 2));

        return $url;
    }

    /**
     * @param OutputInterface|null $output Parameter is null for backward-compatibility. Might be removed in future version
     *
     * @return StoredSet[]
     */
    public function loop(OutputInterface $output = null): array
    {
        if (!$output) {
            $output = new NullOutput();
        }

        $set = new UrlSet();
        $remotes = [];
        $i = 0;

        foreach ($this->resources as $res) {
            /** @var AbstractResource $resource */
            $resource = resolve($res);

            if (!$resource->enabled()) {
                $output->writeln("Skipping resource $res");

                continue;
            }

            $output->writeln("Processing resource $res");

            // The bigger the query chunk size, the better for performance
            // We don't want to make it too high either because extensions impact the amount of data MySQL will have to return from that query
            // The value is arbitrary, as soon as we are above 50k chunks there seem to be diminishing returns
            // With risky improvements enabled, we can bump the value up because the number of columns returned is fixed
            $chunkSize = resolve(SettingsRepositoryInterface::class)->get('fof-sitemap.riskyPerformanceImprovements') ? 150000 : 75000;

            $resource
                ->query()
                ->each(function (AbstractModel|string $item) use (&$output, &$set, $resource, &$remotes, &$i) {
                    $url = new Url(
                        $resource->url($item),
                        $resource->lastModifiedAt($item),
                        $resource->frequency(),
                        $resource->priority()
                    );

                    try {
                        $set->add($url);
                    } catch (SetLimitReachedException $e) {
                        $remotes[$i] = $this->deploy->storeSet($i, $set->toXml());

                        $output->writeln("Storing set $i");

                        $i++;

                        $set = new UrlSet();
                        $set->add($url);
                    }
                }, $chunkSize);
            $remotes[$i] = $this->deploy->storeSet($i, $set->toXml());

            $output->writeln("Storing set $i");

            $i++;

            $set = new UrlSet();
        }

        return $remotes;
    }
}
