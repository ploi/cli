<?php

namespace App\Commands\Server;

use App\Commands\Command;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class RestartServiceCommando extends Command
{
    use EnsureHasToken, HasPloiConfiguration;

    protected $signature = 'service:restart {--server=} {--service=}';

    protected $description = 'Restart a service';

    protected array $server = [];

    protected array $service = ['mysql', 'nginx', 'supervisor'];

    public function handle()
    {
        if ($this->hasPloiConfiguration()) {
            $serverId = $this->configuration->get('server');
            $this->server = $this->ploi->getServerDetails($serverId)['data'];
        } elseif ($this->option('server')) {
            $serverId = $this->option('server');
            $this->server = $this->ploi->getServerDetails($serverId)['data'];
        } else {
            $servers = $this->ploi->getServerList()['data'];
            $serverId = select(
                'Select a server:',
                collect($servers)
                    ->mapWithKeys(fn ($server) => [
                        $server['id'] => $server['name'].' ('.$server['ip_address'].')',
                    ])
                    ->toArray()
            );
            $this->server = $this->ploi->getServerDetails($serverId)['data'];
        }

        $service = $this->option('service');

        if (! $service) {
            $service = select(
                label: 'Select a service:',
                options: $this->service
            );
        }

        try {
            $restartService = spin(
                callback: function () use ($serverId, $service) {
                    $restartService = $this->ploi->restartService($serverId, $service);

                    return $restartService['message'];
                },
                message: 'Restarting service');
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return 1;
        }

        $this->success($restartService);

    }
}
