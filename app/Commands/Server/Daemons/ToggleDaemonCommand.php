<?php

namespace App\Commands\Server\Daemons;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\select;

class ToggleDaemonCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'daemon:toggle {--server=} {--daemon=}';

    protected $description = 'Toggle (pause or resume) a daemon on a server';

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        $daemons = $this->ploi->getDaemons($serverId)['data'];

        $daemonId = $this->option('daemon');

        if (empty($daemonId)) {
            $daemonId = select(
                label: 'Select the daemon to toggle (pause or resume):',
                options: collect($daemons)->mapWithKeys(fn ($daemon) => [$daemon['id'] => $daemon['command'].' => Running on user '.$daemon['system_user'].' with '.$daemon['processes'].' processes on directory '.$daemon['directory']])->toArray(),
                validate: fn ($value) => ! empty($value) ? null : 'Daemon selection is required.',
            );
        }

        $daemon = $this->ploi->pauseDaemon($serverId, $daemonId)['data'];

        if ($daemon['status'] === 'restarting') {
            $this->success('Daemon toggled successfully.');
        } else {
            $this->warn('Failed to toggle daemon.');
        }

    }
}
