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

namespace FoF\Sitemap\Api;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Sitemap\Deploy\DeployInterface;
use FoF\Sitemap\Jobs\TriggerBuildJob;
use Illuminate\Contracts\Queue\Queue;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * API controller for manually triggering sitemap generation.
 *
 * This allows admins to rebuild sitemaps on-demand via the admin UI
 * without waiting for the scheduler or requiring SSH access.
 */
class BuildSitemapController extends AbstractDeleteController
{
    public function __construct(
        protected Queue $queue,
        protected LoggerInterface $logger,
        protected SettingsRepositoryInterface $settings,
        protected DeployInterface $deploy
    ) {
    }

    /**
     * Trigger sitemap build job.
     *
     * @throws \Flarum\User\Exception\PermissionDeniedException
     */
    protected function delete(ServerRequestInterface $request): ResponseInterface
    {
        // Ensure only admins can trigger this
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $serverParams = $request->getServerParams();
        $ip = $serverParams['REMOTE_ADDR'] ?? 'unknown';

        $mode = $this->settings->get('fof-sitemap.mode');
        $deployClass = get_class($this->deploy);
        $queueConnection = config('queue.default');

        $this->logger->info("[FoF Sitemap] API BuildSitemapController called by admin user #{$actor->id} from IP: {$ip}");
        $this->logger->info("[FoF Sitemap] Current mode: {$mode}, Deploy class: {$deployClass}, Queue: {$queueConnection}");

        try {
            // Queue the build job (uses same job as scheduler and auto-rebuild)
            // The Generator will update fof-sitemap.last_build_time when generation completes
            $jobId = $this->queue->push(new TriggerBuildJob());

            $this->logger->info('[FoF Sitemap] Build job successfully queued with ID: '.($jobId ?? 'null'));
        } catch (\Exception $e) {
            $this->logger->error('[FoF Sitemap] Failed to queue build job: '.$e->getMessage());
            $this->logger->error('[FoF Sitemap] Exception trace: '.$e->getTraceAsString());

            throw $e;
        }

        return new EmptyResponse(204);
    }
}
