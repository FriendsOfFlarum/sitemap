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
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
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
        $logger = resolve(LoggerInterface::class);
        $logger->info('[FoF Sitemap] Generator.generate() started, deploy class: '.get_class($this->deploy));
        $logger->info('[FoF Sitemap] Generator resources count: '.count($this->resources));

        if (!$output) {
            $output = new NullOutput();
        }

        $startTime = Carbon::now();

        $url = $this->deploy->storeIndex(
            (new Sitemap($this->loop($output), Carbon::now()))->toXML()
        );

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

        $includeChangefreq = (bool) ($this->settings->get('fof-sitemap.include_changefreq') ?? true);
        $includePriority = (bool) ($this->settings->get('fof-sitemap.include_priority') ?? true);

        // The bigger the query chunk size, the better for performance.
        // We don't want to make it too high because extensions impact the amount of data MySQL returns per query.
        // The value is arbitrary; above ~50k chunks there are diminishing returns.
        // With risky improvements enabled we can bump it because column pruning is also applied.
        $chunkSize = $this->settings->get('fof-sitemap.riskyPerformanceImprovements') ? 150000 : 75000;

        $set = new UrlSet($includeChangefreq, $includePriority);
        $remotes = [];
        $i = 0;

        foreach ($this->resources as $res) {
            /** @var AbstractResource $resource */
            $resource = resolve($res);

            if (!$resource->enabled()) {
                $output->writeln("Skipping resource $res");
                continue;
            }

            $query = $resource->query();

            if ($query instanceof Collection && $query->isEmpty()) {
                $output->writeln("Skipping resource $res (no results)");
                continue;
            }

            $output->writeln("Processing resource $res");

            $foundResults = false;

            $query->each(function (AbstractModel|string $item) use (&$output, &$set, $resource, &$remotes, &$i, &$foundResults, $includeChangefreq, $includePriority) {
                $foundResults = true;

                // Drop any eager-loaded relations that third-party extensions may have
                // added to the model (via $with overrides or event listeners). We only
                // need scalar column values for URL/date generation; keeping relations
                // alive would multiply RAM usage across every model in the chunk.
                if ($item instanceof AbstractModel) {
                    $item->setRelations([]);
                }

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
                    $this->flushSet($set, $i, $output, $remotes);
                    $i++;

                    $set = new UrlSet($includeChangefreq, $includePriority);
                    $set->add($url);
                }
            }, $chunkSize);

            if (!$foundResults) {
                $output->writeln("Note: Resource $res yielded no results during processing");
            }
        }

        // Flush the final partial set.
        if ($set->count() > 0) {
            $this->flushSet($set, $i, $output, $remotes);
        }

        return $remotes;
    }

    /**
     * Finalise a UrlSet, pass its stream to the deploy backend, then close the stream.
     */
    private function flushSet(UrlSet $set, int $index, OutputInterface $output, array &$remotes): void
    {
        $stream = $set->stream();
        $remotes[$index] = $this->deploy->storeSet($index, $stream);
        fclose($stream);

        $memoryMB = round(memory_get_usage(true) / 1024 / 1024, 2);
        $output->writeln("Storing set $index (Memory: {$memoryMB}MB)");

        gc_collect_cycles();
    }
}
