<?php

namespace App\Commands\Site;

use App\Commands\Command as BaseCommand;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\select;

class LogsSiteCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration;

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
            $serverIdentifier = $this->option('server') ?? $this->selectServer();
            $serverId = $this->getServerIdByNameOrIp($serverIdentifier);

            $siteIdentifier = $this->option('site') ?? $this->selectSite($serverId)['domain'];
            $siteId = $this->getSiteIdByDomain($serverId, $siteIdentifier);
        }

        if (! $serverId || ! $siteId) {
            $this->error('Server and site must be valid.');
            exit(1);
        }

        return [$serverId, $siteId];
    }

    protected function getServerIdByNameOrIp(string $identifier): ?int
    {
        $servers = collect($this->ploi->getServerList()['data']);

        $server = $servers->first(fn ($server) => $server['name'] === $identifier || $server['ip_address'] === $identifier);

        if (! $server) {
            $this->error("Server with name or IP '{$identifier}' not found.");
            exit(1);
        }

        return $server['id'];
    }

    protected function getSiteIdByDomain(int $serverId, string $domain): ?int
    {
        $sites = collect($this->ploi->getSiteList($serverId)['data']);

        $site = $sites->first(fn ($site) => $site['domain'] === $domain);

        if (! $site) {
            $this->error("Site with domain '{$domain}' not found on the selected server.");
            exit(1);
        }

        return $site['id'];
    }

    protected function selectServer(): string
    {
        $servers = $this->ploi->getServerList()['data'];

        if (! $servers) {
            $this->error('No servers found! Please create a server first.');
            exit(1);
        }

        return select('Select a server (name or IP):', collect($servers)->pluck('name', 'name')->toArray());
    }

    protected function selectSite($serverId): array
    {
        $sites = collect($this->ploi->getSiteList($serverId)['data'])->pluck('domain', 'domain')->toArray();
        $domain = select('Select a site by domain:', $sites);

        return ['domain' => $domain];
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
