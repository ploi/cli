<?php

namespace App\Commands\Concerns;

use function Laravel\Prompts\search;
use function Laravel\Prompts\spin;

trait InteractWithServer
{
    protected function selectServer(): string
    {
        $servers = $this->ploi->getServerList()['data'];

        if (! $servers) {
            $this->error('No servers found! Please create a server first.');
            exit(1);
        }

        return search(
            label: 'Select a server:',
            options: fn ($search) => collect($servers)
                ->mapWithKeys(fn ($server) => [
                    $server['name'] => $server['name'].' ('.$server['ip_address'].')',
                ])
                ->filter(fn ($name) => str_contains($name, $search))
                ->toArray(),
            scroll: 10
        );
    }

    protected function getServerId(): int
    {
        if ($this->hasPloiConfigurationFile() && ! $this->option('server')) {
            return $this->configuration->get('settings.server');
        }

        $serverIdentifier = $this->option('server') ?? $this->selectServer();

        return $this->getServerIdByNameOrIp($serverIdentifier);
    }

    protected function getServerIdByNameOrIp(string $identifier): ?int
    {
        $servers = collect($this->ploi->getServerList(search: $identifier)['data']);

        $server = $servers->first(fn ($server) => $server['name'] === $identifier ||
            $server['ip_address'] === $identifier
        );

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

    protected function pollServerStatus(string $serverId): ?bool
    {
        $maxAttempts = 100;  // Maximum number of polling attempts per status
        $delay = 15;        // Delay between each attempt in seconds

        $this->info('Server is being created!');
        $isCreated = spin(
            callback: function () use ($serverId, $maxAttempts, $delay) {
                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    $status = $this->ploi->getServerDetails($serverId)['data']['status'];
                    if ($status === 'building') {
                        return true;
                    }
                    sleep($delay);
                }

                return false;
            },
            message: 'Waiting for the server to be created...'
        );

        if (! $isCreated) {
            $this->error('Server creation timed out.');

            return false;
        }

        $this->info('Server is being built!');
        $isBuilding = spin(
            callback: function () use ($serverId, $maxAttempts, $delay) {
                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    $status = $this->ploi->getServerDetails($serverId)['data']['status'];
                    if ($status === 'active') {
                        return true;
                    }
                    sleep($delay);
                }

                return false;
            },
            message: 'Waiting for the server to finish building...'
        );

        if (! $isBuilding) {
            $this->error('Server building timed out.');

            return false;
        }

        $this->success('Server is ready!');

        return true;
    }
}
