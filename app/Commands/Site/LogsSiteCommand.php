<?php

namespace App\Commands\Site;

use App\Commands\Command as BaseCommand;
use App\Traits\EnsureHasToken;
use App\Traits\HasRepo;
use App\Traits\HasPloiConfiguration;
use function Laravel\Prompts\select;

class LogsSiteCommand extends BaseCommand
{
    use EnsureHasToken, HasRepo, HasPloiConfiguration;

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

    protected function getServerAndSite(): array
    {
        if ($this->hasPloiConfiguration()) {
            $serverId = $this->configuration->get('server');
            $siteId = $this->configuration->get('site');
        } else {
            $serverId = $this->option('server') ?? $this->selectServer();
            $siteId = $this->option('site') ?? $this->selectSite($serverId)['id'];
        }

        if (!$serverId || !$siteId) {
            $this->error('Server and Site IDs are required.');
            exit(1);
        }

        return [$serverId, $siteId];
    }

    protected function selectServer(): int|string
    {
        $servers = $this->ploi->getServerList()['data'];

        if (!$servers) {
            $this->error('No servers found! Please create a server first.');
            exit(1);
        }

        return select('Select a server:', collect($servers)->pluck('name', 'id')->toArray());
    }

    protected function selectSite($serverId): array
    {
        $sites = collect($this->ploi->getSiteList($serverId)['data'])->pluck('domain', 'id')->toArray();
        $siteId = select('On which site you want to install the repository?', $sites);

        return ['id' => $siteId, 'domain' => $sites[$siteId]];
    }

    protected function displaySingleLog($serverId, $siteId, $logId): void
    {
        $log = $this->ploi->getSiteLog($serverId, $siteId, $logId)['data'];

        $this->info("Description: {$log['description']}");
        $this->info("Type: " . ($log['type'] ?? 'N/A'));
        $this->info("Created At: {$log['created_at']}");
        $this->info("Relative Time: {$log['created_at_human']}");
        $this->line("Content: {$log['content']}");
    }

    protected function displayMultipleLogs($serverId, $siteId): void
    {
        $logs = $this->ploi->getSiteLogs($serverId, $siteId)['data'];

        $headers = ['ID', 'Description', 'Type', 'Created At', 'Relative Time'];
        $rows = collect($logs)->map(fn($log) => [
            $log['id'],
            $log['description'],
            $log['type'] ?? 'N/A',
            $log['created_at'],
            $log['created_at_human'],
        ])->toArray();

        $this->table($headers, $rows);
    }
}
