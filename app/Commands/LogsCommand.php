<?php

namespace App\Commands;

use App\Services\DeploymentLogPoller;
use App\Traits\EnsureHasToken;
use Exception;

class LogsCommand extends Command
{
    use EnsureHasToken;

    protected $signature = 'logs:stream {server} {site}';

    protected $description = 'Stream deployment logs for a specific site';

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = (int) $this->argument('server');
        $siteId = (int) $this->argument('site');

        try {
            $site = $this->ploi->getSiteDetails($serverId, $siteId)['data'] ?? null;

            if (! $site) {
                $this->error('Site not found.');

                return;
            }

            $currentLog = $site['current_deploy_log'] ?? null;
            $status = $site['status'] ?? null;

            if ($currentLog === null && $status !== 'deploying') {
                $this->info('No deployment in progress for this site.');

                return;
            }

            $this->info("🔄 Streaming deployment logs for {$site['domain']}... (Press Ctrl+C to stop)");
            $this->newLine();

            $poller = new DeploymentLogPoller($this->ploi);

            $poller->pollDeploymentLogs($serverId, $siteId, function ($line) {
                $timestamp = now()->format('H:i:s');
                $this->line("<fg=gray>[{$timestamp}]</> {$line}");
            });

            $this->newLine();
            $this->success('✅ Deployment log streaming completed!');
        } catch (Exception $e) {
            $this->newLine();
            $this->error('❌ Streaming failed: '.$e->getMessage());
        }
    }
}
