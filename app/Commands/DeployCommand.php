<?php

namespace App\Commands;

use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Services\DeploymentLogPoller;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;
use Exception;
use Illuminate\Support\Arr;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\spin;

class DeployCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'deploy {--server=} {--site=} {--schedule=} {--stream : Stream deployment logs in real-time} {--deployment-id= : Specific deployment ID to stream}';

    protected $description = 'Deploy your site to Ploi.io with optional log streaming.';

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();

        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        $data = [];
        $isScheduled = false;

        if ($this->ploi->getSiteDetails($serverId, $siteId)['data']['has_staging']) {
            $this->warn("{$this->site['domain']} has a staging environment.");
            $deployToProduction = confirm(
                label: 'Do you want to deploy to production? (yes/no)',
                default: false
            );

            if ($deployToProduction) {
                $this->deploy($serverId, $siteId, $this->site['domain'], [], true, 'production');

                return;
            }
        }

        $scheduleDatetime = $this->option('schedule');

        if ($scheduleDatetime) {
            $this->validateScheduleDatetime($scheduleDatetime);
            $this->success("Scheduled deployment for {$this->site['domain']} at {$scheduleDatetime}.");
            $data['schedule'] = $scheduleDatetime;
            $isScheduled = true;
        }

        $this->deploy($serverId, $siteId, $this->site['domain'], $data, $isScheduled);

        // Handle streaming after deployment
        if ($this->option('stream') && ! $isScheduled) {
            $deploymentId = $this->option('deployment-id') ?? $this->getLatestDeploymentId($serverId, $siteId);

            if ($deploymentId) {
                $this->streamDeploymentLogs($serverId, $siteId, $deploymentId);
            } else {
                $this->error('No deployment found to stream');
            }
        }
    }

    protected function validateScheduleDatetime(string $datetime): void
    {
        if (! preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}$/', $datetime)) {
            $this->error('Please provide a valid datetime in the format: YYYY-MM-DD HH:MM.');
            exit(1);
        }

        if (strtotime($datetime) < time()) {
            $this->error('The datetime must be in the future.');
            exit(1);
        }
    }

    protected function deploy($serverId, $siteId, $domain, $data, $isScheduled = false, $toProduction = false): void
    {
        if ($toProduction) {
            $this->info("Deploying to production for {$domain}...");
            $deploying = $this->ploi->deployToProduction($serverId, $siteId);
        } else {
            $this->info("Deploying {$domain}...");
            $deploying = $this->ploi->deploySite($serverId, $siteId, $data);
        }

        if (isset($deploying['error'])) {
            $this->error($deploying['error']);
            exit();
        }

        $this->info(Arr::first($deploying['data'])['message']);

        if ($isScheduled) {
            return;
        }

        $this->pollDeploymentStatus($serverId, $siteId, $domain);
    }

    protected function pollDeploymentStatus(string $serverId, string $siteId, string $domain): void
    {
        $maxAttempts = 60;   // Maximum number of polling attempts (10 minutes total with 10-second delay)
        $delay = 5;         // Delay between each attempt in seconds

        $this->info('Deployment initiated!');
        $status = spin(
            callback: function () use ($serverId, $siteId, $domain, $maxAttempts, $delay) {
                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    $deploymentStatus = $this->ploi->getSiteDetails($serverId, $siteId)['data']['status'] ?? 'deploying';

                    // If we get a final status, return it
                    if (in_array($deploymentStatus, ['active', 'deploy-failed'])) {
                        return [
                            'status' => $deploymentStatus,
                            'domain' => $domain,
                        ];
                    }

                    sleep($delay);
                }

                // If we've exceeded max attempts, return timeout status
                return [
                    'status' => 'timeout',
                    'domain' => $domain,
                ];
            },
            message: 'Checking deployment status...'
        );

        // Handle the deployment result
        match ($status['status']) {
            'active' => $this->handleSuccessfulDeployment($serverId, $siteId, $status['domain']),
            'deploy-failed' => $this->handleFailedDeployment($serverId, $siteId),
            'timeout' => $this->warn('Deployment status check timed out. Please check manually.'),
            default => $this->warn('Deployment status is unknown. Please check manually.')
        };
    }

    /**
     * Stream deployment logs in real-time
     *
     * @return void
     */
    private function streamDeploymentLogs(int $serverId, int $siteId, int $deploymentId)
    {
        $this->info('🔄 Streaming deployment logs...');
        $this->newLine();

        $poller = new DeploymentLogPoller(config('ploi.token'));

        try {
            $poller->pollDeploymentLogs($serverId, $siteId, $deploymentId, function ($line) {
                // Format the log line with timestamp
                $timestamp = now()->format('H:i:s');
                $this->line("<fg=gray>[{$timestamp}]</fg=gray> {$line}");
            });

            $this->newLine();
            $this->success('✅ Deployment streaming completed!');

        } catch (Exception $e) {
            $this->newLine();
            $this->error('❌ Streaming failed: '.$e->getMessage());
        }
    }

    /**
     * Get the latest deployment ID for streaming
     */
    private function getLatestDeploymentId(int $serverId, int $siteId): ?int
    {
        $poller = new DeploymentLogPoller(config('ploi.token'));

        try {
            $deployment = $poller->getLatestDeployment($serverId, $siteId);

            return $deployment['id'] ?? null;
        } catch (Exception $e) {
            $this->error('Failed to get latest deployment: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Handle successful deployment completion
     */
    private function handleSuccessfulDeployment(string $serverId, string $siteId, string $domain): void
    {
        $this->success("Deployment completed successfully. Site is live on: {$domain}");
    }

    /**
     * Handle failed deployment
     */
    private function handleFailedDeployment(string $serverId, string $siteId): void
    {
        $this->error('Your recent deployment has failed, please check recent deploy log for errors.');

        // Show link to deployment logs if not streaming
        if (! $this->option('stream')) {
            $this->showLogLink($serverId, $siteId);
        }
    }

    /**
     * Show link to deployment logs
     */
    private function showLogLink(string $serverId, string $siteId): void
    {
        try {
            // Get the latest deployment log ID
            $logs = $this->ploi->getSiteLogs($serverId, $siteId, 1)['data'];

            if (! empty($logs)) {
                $latestLogId = $logs[0]['id'];
                $logUrl = "https://ploi.io/panel/servers/{$serverId}/sites/{$siteId}/logs/modals/{$latestLogId}";

                $this->line('');
                $this->info("📋 View deployment logs: {$logUrl}");
            }
        } catch (Exception $e) {
            // Silently fail if we can't get log link - not critical
        }
    }
}
