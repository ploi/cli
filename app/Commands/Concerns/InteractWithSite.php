<?php

namespace App\Commands\Concerns;

use App\Traits\DomainSuggestions;

use function Laravel\Prompts\search;

trait InteractWithSite
{
    use DomainSuggestions;

    protected function getServerAndSite(): array
    {
        if ($this->hasPloiConfigurationFile() &&
            !$this->option('server') &&
            !$this->option('site')) {
            $serverId = $this->configuration->get('settings.server');
            $siteId = $this->configuration->get('settings.site');
        } else {
            $serverIdentifier = $this->option('server')
                ?? $this->selectServer();
            $serverId = $this->getServerIdByNameOrIp($serverIdentifier);

            $siteIdentifier = $this->option('site')
                ?? $this->selectSite($serverId)['domain'];
            $siteId = $this->getSiteIdByDomain($serverId, $siteIdentifier);
        }

        if (!$serverId || !$siteId) {
            $this->error('Server and site must be valid.');
            exit(1);
        }

        return [$serverId, $siteId];
    }

    protected function selectSite($serverId): array
    {
        $sites = collect($this->ploi->getSiteList($serverId)['data'])
            ->pluck('domain', 'domain');

        $domain = search(
            label: 'Select a site by domain:',
            options: fn(string $search) => $sites
                ->filter(fn($name) => str_contains($name, $search))
                ->toArray(),
            scroll: 10
        );

        return ['domain' => $domain];
    }

    protected function getSiteIdByDomain(int $serverId, string $domain): ?int
    {
        $sites = collect($this->ploi->getSiteList($serverId)['data']);
        $availableDomains = $sites->pluck('domain')->toArray();

        $site = $sites->first(fn ($site) => $site['domain'] === $domain);

        if (!$site) {
            $selectedDomain = $this->getDomainSuggestionsWithSelection($domain, $availableDomains);

            if ($selectedDomain) {
                $site = $sites->first(fn ($site) => $site['domain'] === $selectedDomain);
            } else {
                $this->error("Site with domain '{$domain}' not found on the selected server.");
                exit(1);
            }
        }

        return $site['id'];
    }
}
