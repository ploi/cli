<?php

namespace App\Commands;

use AllowDynamicProperties;
use App\Concerns\EnsureHasPloiConfiguration;
use App\Support\Configuration;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\select;
use App\Services\SiteService;
use App\Services\ServerService;

#[AllowDynamicProperties] class TestCommand extends Command
{
    use EnsureHasPloiConfiguration;

    protected $signature = 'deploy {site?}';
    protected $description = 'Deploy a site';

    public function __construct()
    {
        parent::__construct();
        $this->serverService = new ServerService();
        $this->siteService = new SiteService();
    }

    public function handle(Configuration $configuration): void
    {
        $serverId = $configuration->get('server');
        $siteId = $configuration->get('site');

        if (!$this->hasPloiConfiguration()) {
            $servers = $this->serverService->list()->getData();
            $serverId = select('Select a server:', collect($servers)->pluck('name', 'id')->toArray());

            $sites = $this->siteService->list($serverId)->getData();
            $siteId = select('Select a site:', collect($sites)->pluck('domain', 'id')->toArray());
        }

        $sites = $this->siteService->list($serverId)->getData();
        $siteName = collect($sites)->firstWhere('id', $siteId)->domain;

        $this->info("<fg=blue>==></> <options=bold>Deploying site: {$siteName}</>");
        $this->runDeployment($serverId, $siteId, $siteName);

        // $this->showLogs($this->argument('site'));
    }

    private function runDeployment($serverId, $siteId, $siteName): void
    {

        $siteService = new SiteService();

        spin(
             callback: fn () => $siteService->deploy($serverId, $siteId),
             message: 'Running deployment...'
         );

        // keep polling on the site status to see if deployment is completed or failed
        $deploymentStatus = 'deploying';
        $maxAttempts = 30;
        $attempts = 0;

        while ($deploymentStatus === 'deploying' && $attempts < $maxAttempts) {
            $attempts++;

            spin(
                callback: function () use ($siteService, $serverId, $siteId, &$deploymentStatus) {
                    $status = $siteService->getSiteStatus($serverId, $siteId);
                    $deploymentStatus = $status->status ?? 'deploying';
                    if ($deploymentStatus === 'deploy-failed') {
                        return false; // Stop the spin function immediately
                    }
                    sleep(5);
                    return $status; // Return the status
                },
                message: "Checking deployment status..."
            );

            if ($deploymentStatus === 'active') {
                $this->info("<fg=green>==></> <options=bold>Deployment completed successfully.</>");
                $this->info("<fg=green>==></> <options=bold>Site is now live on: {$siteName}.</>");
                break;
            } elseif ($deploymentStatus === 'deploy-failed') {
                $this->info("<fg=red>==></> <options=bold>Deployment failed.</>");
                break;
            }
        }

        if ($deploymentStatus === 'deploying') {
            $this->info("<fg=yellow>==></> <options=bold>Deployment is taking longer than expected. Please check manually.</>");
            exit;
        }
    }

    private function showLogs($site): void
    {
        if ($this->option('log')) {
            $this->warn('Waiting for logs...');

            do {
                $logId = $this->getDeploymentLog($ploi, $configuration);
                sleep(1);
            } while ($logId == null);

            $this->watchLog($ploi, $configuration, $logId);
        }
    }

    private function getDeploymentLog(Ploi $ploi, Configuration $configuration): ?string
    {
        $logs = $ploi->getLogs($configuration->get('server'), $configuration->get('site'));
        if (count($logs) == 0) return null;
        $latestLog = $logs[0];

        if (!Str::of($latestLog['created_at_human'])->contains('seconds ago')) {
            return null;
        }

        if ((string)(Str::of($latestLog['created_at_human'])->before('seconds ago')) < 5) {
            return $latestLog['id'];
        }

        return null;
    }

    private function watchLog(Ploi $ploi, Configuration $configuration, string $logId)
    {
        $printed = "";

        while (!str_contains($printed, 'Application deployed!')) {
            $contents = $ploi->getLog($configuration->get('server'), $configuration->get('site'), $logId)['content'];
            $contents = str_replace($printed, "", $contents);
            $this->info($contents);
            $printed .= $contents;
            sleep(1);
        }
    }
}
