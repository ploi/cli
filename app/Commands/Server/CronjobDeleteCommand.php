<?php

namespace App\Commands\Server;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class CronjobDeleteCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'cronjobs:delete {--server=} {--cronjob-id= : The ID of the cronjob to delete}';

    protected $description = 'Delete a cronjob on a server';

    protected array $server = [];

    public function handle()
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        try {
            $cronjobId = $this->option('cronjob-id');
            if (empty($cronjobId)) {
                $cronjobs = $this->ploi->getCronjobs($serverId)['data'];
                if (empty($cronjobs)) {
                    error('No cronjobs found on the selected server.');

                    return 1;
                }

                $cronjobId = select(
                    label: 'Select the cronjob to delete:',
                    options: collect($cronjobs)->mapWithKeys(fn ($cronjob) => [$cronjob['id'] => '['.$cronjob['id'].'] '.$cronjob['command'].' ('.$cronjob['frequency'].') via user '.$cronjob['user']])->toArray(),
                    validate: fn ($value) => ! empty($value) ? null : 'Cronjob selection is required.',
                );
            }

            spin(
                callback: fn () => $this->ploi->deleteCronjob($serverId, $cronjobId),
                message: 'Deleting cronjob...',
            );

            $this->success('Cronjob deleted successfully.');

        } catch (\Exception $e) {
            error('An error occurred while deleting the Cronjob: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
