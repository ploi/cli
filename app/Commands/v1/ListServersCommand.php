<?php

namespace App\Commands\v1;

use App\Concerns\EnsureHasToken;
use App\Support\Ploi;
use LaravelZero\Framework\Commands\Command;

class ListServersCommand extends Command
{
    use EnsureHasToken;

    protected $signature = 'server:list';

    protected $description = 'Prints a list of all servers';

    public function handle(Ploi $ploi)
    {
        $this->ensureHasToken();

        $servers = $ploi->getServers();

        $this->table([
            'ID',
            'Type',
            'Name',
            'IP Address',
            'Number of Sites',
            'Status'
        ], collect($servers)->map(function ($server) {
            return [
                'id' => $server['id'],
                'type' => $server['type'],
                'name' => $server['name'],
                'ip_address' => $server['ip_address'],
                'number_of_sites' => $server['sites_count'],
                'status' => $server['status'],
            ];
        }));
    }
}
