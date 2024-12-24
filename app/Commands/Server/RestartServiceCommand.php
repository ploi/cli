<?php

namespace App\Commands\Server;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class RestartServiceCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'service:restart {--server=} {--service=}';

    protected $description = 'Restart a service';

    protected array $services = ['mysql', 'nginx', 'supervisor'];

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        $service = $this->option('service');

        if (! $service) {
            $service = select(
                label: 'Select a service:',
                options: $this->services
            );
        }

        try {
            $restartServiceMessage = spin(
                callback: fn () => $this->ploi->restartService($serverId, $service)['message'],
                message: 'Restarting service...'
            );

            $this->success($restartServiceMessage);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
