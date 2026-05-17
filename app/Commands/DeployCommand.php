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

    protected $signature = 'deploy {--server=} {--site=} {--schedule=} {--no-stream : Disable real-time deployment log streaming}';

    protected $description = 'Deploy your site to Ploi.io with optional log streaming.';

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();

        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        $data = [];
        $isScheduled = false;

        if ($this->site['has_staging']) {
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

        $firstValue = Arr::first($deploying['data']);
        $this->info(is_array($firstValue) ? $firstValue['message'] : $firstValue);

        if ($isScheduled) {
            return;
        }

        if ($this->option('no-stream')) {
            $this->pollDeploymentStatus($serverId, $siteId, $domain);

            return;
        }

        $this->streamAndAwait($serverId, $siteId, $domain);
    }

    protected function pollDeploymentStatus(string $serverId, string $siteId, string $domain): void
    {
        $maxAttempts = 60;   // Maximum number of polling attempts (5 minutes total with 5-second delay)
        $delay = 5;          // Delay between each attempt in seconds

        $this->info('Deployment initiated!');

        $status = spin(
            callback: function () use ($serverId, $siteId, $domain, $maxAttempts, $delay) {
                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    $deploymentStatus = $this->ploi->getSiteDetails($serverId, $siteId)['data']['status'] ?? 'deploying';

                    if (in_array($deploymentStatus, ['active', 'deploy-failed'])) {
                        return [
                            'status' => $deploymentStatus,
                            'domain' => $domain,
                        ];
                    }

                    sleep($delay);
                }

                return [
                    'status' => 'timeout',
                    'domain' => $domain,
                ];
            },
            message: 'Checking deployment status...'
        );

        match ($status['status']) {
            'active' => $this->handleSuccessfulDeployment($serverId, $siteId, $status['domain']),
            'deploy-failed' => $this->handleFailedDeployment($serverId, $siteId),
            'timeout' => $this->warn('Deployment status check timed out. Please check manually.'),
            default => $this->warn('Deployment status is unknown. Please check manually.'),
        };
    }

    private function streamAndAwait(int $serverId, int $siteId, string $domain): void
    {
        $this->info('🔄 Streaming deployment logs...');
        $this->newLine();

        $poller = new DeploymentLogPoller($this->ploi);

        try {
            $status = $poller->pollDeploymentLogs($serverId, $siteId, function ($line) {
                $timestamp = now()->format('H:i:s');
                $this->line("<fg=gray>[{$timestamp}]</> {$line}");
            });
        } catch (Exception $e) {
            $this->newLine();
            $this->warn('Log streaming failed ('.$e->getMessage().'), falling back to status checks...');
            $this->pollDeploymentStatus($serverId, $siteId, $domain);

            return;
        }

        $this->newLine();

        match ($status) {
            'active' => $this->handleSuccessfulDeployment($serverId, $siteId, $domain),
            'deploy-failed' => $this->handleFailedDeployment($serverId, $siteId),
            default => $this->warn('Deployment status check timed out. Please check manually.'),
        };
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

        // When streaming, the failed log output is already shown inline.
        if ($this->option('no-stream')) {
            $this->showLogLink($serverId, $siteId);
        }
    }

    /**
     * Show link to deployment logs
     */
    private function showLogLink(string $serverId, string $siteId): void
    {
        try {
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
