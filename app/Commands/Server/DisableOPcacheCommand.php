<?php

namespace App\Commands\Server;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\confirm;

class DisableOPcacheCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'server:disable-opcache {--server=}';

    protected $description = 'Disable OPcache on a server';

    protected array $server = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        if ($this->server['opcache'] === false) {
            $this->error('OPcache is already disabled on this server.');

            $enableOPcache = confirm('Do you want to enable OPcache?');
            if ($enableOPcache) {
                $this->ploi->enableOPcache($serverId);
                $this->success('OPcache has been enabled.');
            }
            exit(1);
        }

        try {
            $this->ploi->disableOPcache($serverId);

            $this->success('OPcache disabled successfully.');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
