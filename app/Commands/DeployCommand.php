<?php

namespace App\Commands;

use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;
use Illuminate\Support\Arr;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\spin;

class DeployCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'deploy {--server=} {--site=} {--schedule=}';

    protected $description = 'Deploy your site to Ploi.io.';

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
                $this->deploy($serverId, $siteId, $this->site['domain'], [], true);

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
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}$/', $datetime)) {
            $this->error('Please provide a valid datetime in the format: YYYY-MM-DD HH:MM.');
            exit(1);
        }

        if (strtotime($datetime) < time()) {
            $this->error('The datetime must be in the future.');
            exit(1);
        }
    }

    protected function deploy($serverId, $siteId, $domain, $data, $isScheduled = false): void
    {
        $deploying = $this->ploi->deploySite($serverId, $siteId, $data);

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
            'active' => $this->success("Deployment completed successfully. Site is live on: {$status['domain']}"),
            'deploy-failed' => $this->error('Your recent deployment has failed, please check recent deploy log for errors.'),
            'timeout' => $this->warn('Deployment status check timed out. Please check manually.'),
            default => $this->warn('Deployment status is unknown. Please check manually.')
        };
    }
}
