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
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Deploy\DeployInterface;
use FoF\Sitemap\Deploy\StoredSet;
use FoF\Sitemap\Exceptions\SetLimitReachedException;
use FoF\Sitemap\Resources\Resource as AbstractResource;
use FoF\Sitemap\Sitemap\Sitemap;
use FoF\Sitemap\Sitemap\Url;
use FoF\Sitemap\Sitemap\UrlSet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Generator
{
    public function __construct(
        protected DeployInterface $deploy,
        protected array $resources,
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function generate(?OutputInterface $output = null): ?string
    {
        $logger = resolve(\Psr\Log\LoggerInterface::class);
        $logger->info('[FoF Sitemap] Generator.generate() started, deploy class: '.get_class($this->deploy));
        $logger->info('[FoF Sitemap] Generator resources count: '.count($this->resources));

        if (!$output) {
            $output = new NullOutput();
        }

        $startTime = Carbon::now();

        $now = Carbon::now();

        $url = $this->deploy->storeIndex(
            (new Sitemap($this->loop($output), $now))->toXML()
        );

        // Update last build time
        $this->settings->set('fof-sitemap.last_build_time', time());

        $output->writeln('Completed in '.$startTime->diffForHumans(null, CarbonInterface::DIFF_ABSOLUTE, true, 2));

        return $url;
    }

    /**
     * @param OutputInterface|null $output Parameter is null for backward-compatibility. Might be removed in future version
     *
     * @return StoredSet[]
     */
    public function loop(?OutputInterface $output = null): array
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

            // Get query once and reuse
            $query = $resource->query();

            // For Collections, check if empty immediately to avoid unnecessary processing
            if ($query instanceof Collection && $query->isEmpty()) {
                $output->writeln("Skipping resource $res (no results)");
                continue;
            }

            $output->writeln("Processing resource $res");

            // Track if we found any results (for Builder queries where we can't check upfront)
            $foundResults = false;

            // The bigger the query chunk size, the better for performance
            // We don't want to make it too high either because extensions impact the amount of data MySQL will have to return from that query
            // The value is arbitrary, as soon as we are above 50k chunks there seem to be diminishing returns
            // With risky improvements enabled, we can bump the value up because the number of columns returned is fixed
            $chunkSize = $this->settings->get('fof-sitemap.riskyPerformanceImprovements') ? 150000 : 75000;

            $query->each(function (mixed $item) use (&$output, &$set, $resource, &$remotes, &$i, &$foundResults) {
                $foundResults = true;
                $url = new Url(
                    $resource->url($item),
                    $resource->lastModifiedAt($item),
                    $resource->dynamicFrequency($item) ?? $resource->frequency(),
                    $resource->dynamicPriority($item) ?? $resource->priority(),
                    $resource->alternatives($item)
                );

                try {
                    $set->add($url);
                } catch (SetLimitReachedException) {
                    $remotes[$i] = $this->deploy->storeSet($i, $set->toXml());

                    $memoryMB = round(memory_get_usage(true) / 1024 / 1024, 2);
                    $output->writeln("Storing set $i (Memory: {$memoryMB}MB)");

                    // Explicitly clear the URLs array to free memory before creating new set
                    $set->urls = [];

                    // Force garbage collection after storing large sets
                    if ($i % 5 == 0) {
                        gc_collect_cycles();
                    }

                    $i++;

                    $set = new UrlSet();
                    $set->add($url);
                }
            }, $chunkSize);

            // Log if no results were found during iteration
            if (!$foundResults) {
                $output->writeln("Note: Resource $res yielded no results during processing");
            }

            // Only store the set if it contains URLs (avoid empty sets)
            if (count($set->urls) > 0) {
                $remotes[$i] = $this->deploy->storeSet($i, $set->toXml());

                $memoryMB = round(memory_get_usage(true) / 1024 / 1024, 2);
                $output->writeln("Storing set $i (Memory: {$memoryMB}MB)");

                // Explicitly clear the URLs array to free memory
                $set->urls = [];

                $i++;

                $set = new UrlSet();
            }
        }

        return $remotes;
    }
}
