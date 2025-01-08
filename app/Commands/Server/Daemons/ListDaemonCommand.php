<?php

namespace App\Commands\Server\Daemons;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

class ListDaemonCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'daemon:list {--server=}';

    protected $description = 'Get the daemons on a server';

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        $daemons = $this->ploi->getDaemons($serverId)['data'];

        $headers = ['ID', 'Status', 'Command', 'User', 'Processes'];
        $rows = collect($daemons)->map(fn ($daemon) => [
            $daemon['id'],
            $daemon['status'],
            $daemon['command'],
            $daemon['system_user'],
            $daemon['processes'],
        ])->toArray();

        $this->table($headers, $rows);
    }
}
