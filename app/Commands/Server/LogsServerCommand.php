<?php

namespace App\Commands\Site;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

class LogsSiteCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'logs:site {logid?} {--server=} {--site=}';

    protected $description = 'Get the logs of a site';

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        $logId = $this->argument('logid');

        $logId ? $this->displaySingleLog($serverId, $siteId, $logId) : $this->displayMultipleLogs($serverId, $siteId);
    }

    protected function displaySingleLog($serverId, $siteId, $logId): void
    {
        $log = $this->ploi->getSiteLog($serverId, $siteId, $logId)['data'];

        $this->info("Description: {$log['description']}");
        $this->info('Type: '.($log['type'] ?? 'N/A'));
        $this->info("Created At: {$log['created_at']}");
        $this->info("Relative Time: {$log['created_at_human']}");
        $this->line("Content: {$log['content']}");
    }

    protected function displayMultipleLogs($serverId, $siteId): void
    {
        $logs = $this->ploi->getSiteLogs($serverId, $siteId)['data'];

        $headers = ['ID', 'Description', 'Type', 'Created At', 'Relative Time'];
        $rows = collect($logs)->map(fn ($log) => [
            $log['id'],
            $log['description'],
            $log['type'] ?? 'N/A',
            $log['created_at'],
            $log['created_at_human'],
        ])->toArray();

        $this->table($headers, $rows);
    }
}
