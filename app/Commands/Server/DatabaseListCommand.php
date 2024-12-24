<?php

namespace App\Commands\Server;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

class DatabaseListCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'database:list {--server=}';

    protected $description = 'List databases on a server';

    protected array $server = [];

    public function handle()
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        $this->server = $this->ploi->getServerDetails($serverId)['data'];
        $databases = $this->ploi->databaseList($serverId)['data'];

        if (! $databases) {
            $this->comment('No databases found');

            return 0;
        }

        $this->info('Databases for '.$this->server['name'].' ('.$this->server['ip_address'].')');

        $this->table([
            'ID', 'Name', 'Type', 'Status', 'Site Domain', 'Created At',
        ], collect($databases)->map(fn ($database) => [
            $database['id'],
            $database['name'],
            $database['type'],
            $database['status'],
            $database['site']['root_domain'] ?? 'N/A',
            $database['created_at'],
        ])->toArray());

    }
}
