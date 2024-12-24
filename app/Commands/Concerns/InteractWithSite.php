<?php

namespace App\Commands\Concerns;

use function Laravel\Prompts\select;

trait InteractWithSite
{
    protected function getServerAndSite(): array
    {
        if ($this->hasPloiConfiguration() && ! $this->option('server') && ! $this->option('site')) {
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

    protected function selectSite($serverId): array
    {
        $sites = collect($this->ploi->getSiteList($serverId)['data'])->pluck('domain', 'domain')->toArray();
        $domain = select('Select a site by domain:', $sites);

        return ['domain' => $domain];
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
}
