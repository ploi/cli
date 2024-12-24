<?php

namespace App\Commands;

use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

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
        if (! preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}$/', $datetime)) {
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

        if (! isset($deploying['message'])) {
            $this->error($deploying['error']);
            exit();
        }

        $this->info($deploying['message']);

        if ($isScheduled) {
            return;
        }

        $statusChecked = spin(
            callback: function () use ($serverId, $siteId, $domain) {
                while (true) {
                    sleep(10);

                    $deploymentStatus = $this->ploi->getSiteDetails($serverId, $siteId)['data']['status'] ?? 'deploying';

                    $statusMap = [
                        'active' => ['type' => 'success', 'message' => "Deployment completed successfully. Site is live on: {$domain}"],
                        'deploy-failed' => ['type' => 'error', 'message' => 'Your recent deployment has failed, please check recent deploy log for errors.'],
                    ];

                    return $statusMap[$deploymentStatus] ?? ['type' => 'warn', 'message' => 'Deployment status is unknown. Please check manually.'];
                }
            },
            message: 'Checking deployment status...'
        );

        $this->console($statusChecked['message'], $statusChecked['type']);
    }
}
