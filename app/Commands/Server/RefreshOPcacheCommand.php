<?php

namespace App\Commands\Server;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

class RefreshOPcacheCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'server:refresh-opcache {--server=}';

    protected $description = 'Refresh OPcache on a server';

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        try {
            $this->ploi->refreshOPcache($serverId);

            $this->success('OPcache refreshed successfully.');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
