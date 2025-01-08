<?php

namespace App\Commands\Server\Daemons;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class RestartDaemonCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'daemon:restart {--server=} {--daemon=}';

    protected $description = 'Restart a daemon on a server';

    public function handle()
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        $daemons = $this->ploi->getDaemons($serverId)['data'];

        $daemonId = $this->option('daemon');

        if (empty($daemonId)) {
            $daemonId = select(
                label: 'Select the daemon to restart:',
                options: collect($daemons)->mapWithKeys(fn ($daemon) => [$daemon['id'] => $daemon['command'].' => Running on user '.$daemon['system_user'].' with '.$daemon['processes'].' processes on directory '.$daemon['directory']])->toArray(),
                validate: fn ($value) => ! empty($value) ? null : 'Daemon selection is required.',
            );
        }

        $daemon = $this->ploi->restartDaemon($serverId, $daemonId)['data'];

        $restartStatus = spin(
            callback: fn () => $daemon['status'] === 'restarting',
            message: 'Restarting daemon...',
        );

        if (! $restartStatus) {
            $this->error('Failed to restart daemon.');

            return 0;
        }

        $this->success('Daemon restarted successfully.');

    }
}
