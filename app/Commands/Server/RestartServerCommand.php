<?php

namespace App\Commands\Server;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

class RestartServerCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'server:restart {--server=}';

    protected $description = 'Restart a server';

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        try {
            $restartServerResponse = $this->ploi->restartServer($serverId)['data'];

            $this->success($restartServerResponse[0]['message']);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
