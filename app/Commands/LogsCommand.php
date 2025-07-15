<?php

namespace App\Commands;

use App\Services\DeploymentLogPoller;
use App\Traits\EnsureHasToken;
use Exception;

class LogsCommand extends Command
{
    use EnsureHasToken;

    protected $signature = 'logs:stream {server} {site} {--deployment-id= : Specific deployment ID to stream}';

    protected $description = 'Stream deployment logs for a specific site';

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = (int) $this->argument('server');
        $siteId = (int) $this->argument('site');
        $deploymentId = $this->option('deployment-id');

        $poller = new DeploymentLogPoller(config('ploi.token'));

        try {
            // If no deployment ID provided, get the latest or active deployment
            if (! $deploymentId) {
                $deployment = $poller->getActiveDeployment($serverId, $siteId)
                    ?? $poller->getLatestDeployment($serverId, $siteId);

                if (! $deployment) {
                    $this->error('No deployment found for this site');

                    return;
                }

                $deploymentId = $deployment['id'];
                $this->info("Streaming logs for deployment #{$deploymentId} (status: {$deployment['status']})");
            } else {
                $this->info("Streaming logs for deployment #{$deploymentId}");
            }

            $this->info('🔄 Streaming deployment logs... (Press Ctrl+C to stop)');
            $this->newLine();

            $poller->pollDeploymentLogs($serverId, $siteId, $deploymentId, function ($line) {
                $timestamp = now()->format('H:i:s');
                $this->line("<fg=gray>[{$timestamp}]</fg=gray> {$line}");
            });

            $this->newLine();
            $this->success('✅ Deployment log streaming completed!');

        } catch (Exception $e) {
            $this->newLine();
            $this->error('❌ Streaming failed: '.$e->getMessage());
        }
    }
}
