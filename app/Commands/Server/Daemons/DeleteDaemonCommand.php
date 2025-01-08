<?php

namespace App\Commands\Server\Daemons;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class DeleteDaemonCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'daemon:delete {--server=} {--daemon-id= : The ID of the daemon to delete}';

    protected $description = 'Delete a daemon on a server';

    protected array $server = [];

    public function handle()
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        try {
            $daemonId = $this->option('daemon-id');
            if (empty($daemonId)) {
                $daemons = $this->ploi->getDaemons($serverId)['data'];
                if (empty($daemons)) {
                    error('No daemons found on the selected server.');

                    return 1;
                }

                $daemonId = select(
                    label: 'Select the daemon to delete:',
                    options: collect($daemons)->mapWithKeys(fn ($daemon) => [$daemon['id'] => $daemon['command'].' => Running on user '.$daemon['system_user'].' with '.$daemon['processes'].' processes on directory '.$daemon['directory']])->toArray(),
                    validate: fn ($value) => ! empty($value) ? null : 'Daemon selection is required.',
                );
            }

            spin(
                callback: fn () => $this->ploi->deleteDaemon($serverId, $daemonId),
                message: 'Deleting daemon...',
            );

            $this->success('Daemon deleted successfully.');

        } catch (\Exception $e) {
            error('An error occurred while deleting the Cronjob: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
