<?php

namespace App\Commands\Concerns;

use function Laravel\Prompts\select;

trait InteractWithServer
{
    protected function selectServer(): string
    {
        $servers = $this->ploi->getServerList()['data'];

        if (! $servers) {
            $this->error('No servers found! Please create a server first.');
            exit(1);
        }

        return select(
            'Select a server:',
            collect($servers)
                ->mapWithKeys(fn ($server) => [
                    $server['name'] => $server['name'].' ('.$server['ip_address'].')',
                ])
                ->toArray(),
            scroll: 10
        );
    }

    protected function getServerId(): int
    {
        if ($this->hasPloiConfiguration() && ! $this->option('server')) {
            return $this->configuration->get('settings.server');
        }

        $serverIdentifier = $this->option('server') ?? $this->selectServer();

        return $this->getServerIdByNameOrIp($serverIdentifier);
    }

    protected function getServerIdByNameOrIp(string $identifier): ?int
    {
        $servers = collect($this->ploi->getServerList(search: $identifier)['data']);

        $server = $servers->first(fn ($server) => $server['name'] === $identifier || $server['ip_address'] === $identifier);

        if (! $server) {
            $this->error("Server with name or IP '{$identifier}' not found.");
            exit(1);
        }

        return $server['id'];
    }

    protected function systemUsers()
    {
        return $this->ploi->getSystemUsers($this->getServerId());
    }
}
