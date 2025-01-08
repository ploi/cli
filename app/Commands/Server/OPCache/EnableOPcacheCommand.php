<?php

namespace App\Commands\Server\OPCache;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

class EnableOPcacheCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'opcache:enable {--server=}';

    protected $description = 'Enable OPcache on a server';

    protected array $server = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        if ($this->server['opcache']) {
            $this->error('OPcache is already enabled on this server.');

            $disableOPcache = $this->confirm('Do you want to disable OPcache?');
            if ($disableOPcache) {
                $this->ploi->disableOPcache($serverId);
                $this->success('OPcache has been disabled.');
            }
            exit(1);
        }

        try {
            $this->ploi->enableOPcache($serverId);

            $this->success('OPcache enabled successfully.');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
