<?php

namespace App\Commands\Server;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class DeleteServerCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'server:delete {--server=} {--force}';

    protected $description = 'Delete a server';

    protected array $server = [];

    public function handle()
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        $this->warn('!! This action is irreversible !!');
        $this->warn('!! All data will be deleted !!');

        $confirm = $this->option('force') || text(
            label: 'Type the server name to confirm deletion: '.$this->server['name'],
            validate: fn (string $value) => match (true) {
                $value !== $this->server['name'] => 'The server name does not match.',
                default => null,
            }
        );

        if (! $confirm) {
            $this->info('Server deletion aborted.');

            return 0;
        }

        try {
            spin(
                callback: fn () => $this->ploi->deleteServer($serverId),
                message: 'Deleting server...',
            );

            $this->success('Server deleted successfully.');

        } catch (\Exception $e) {
            error('An error occurred while deleting the server: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
