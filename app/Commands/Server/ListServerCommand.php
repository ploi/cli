<?php

namespace App\Commands\Server;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

class ListServerCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'server:list';

    protected $description = 'Get all servers';

    public function handle(): void
    {
        $this->ensureHasToken();

        $servers = $this->ploi->getServerList()['data'];

        $headers = ['ID', 'Name', 'IP Address', 'PHP Version', 'MySQL Version', 'Sites', 'Status', 'Created At'];
        $rows = collect($servers)->map(fn ($server) => [
            $server['id'],
            $server['name'],
            $server['ip_address'],
            $server['php_version'],
            $server['mysql_version'],
            $server['sites_count'],
            $server['status'],
            $server['created_at'],
        ])->toArray();

        $this->table($headers, $rows);
    }
}
