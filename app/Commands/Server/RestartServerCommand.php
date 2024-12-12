<?php

namespace App\Commands\Server;

use App\Commands\Command;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\select;

class RestartServerCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration;

    protected $signature = 'server:restart {--server=}';

    protected $description = 'Restart a server';

    protected array $server = [];

    public function handle()
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        if (! $serverId) {
            $serverId = $this->promptServerSelection();
        }

        try {
            $this->server = $this->ploi->getServerDetails($serverId)['data'];
            $restartServer = $this->ploi->restartServer($serverId);
            $this->success($restartServer['message']);
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }

    private function getServerId(): ?string
    {
        if ($this->hasPloiConfiguration()) {
            return $this->configuration->get('server');
        }

        return $this->option('server');
    }

    private function promptServerSelection(): string
    {
        $servers = $this->ploi->getServerList()['data'];

        return select(
            'Select a server:',
            collect($servers)
                ->mapWithKeys(fn ($server) => [
                    $server['id'] => $server['name'].' ('.$server['ip_address'].')',
                ])
                ->toArray()
        );
    }
}
