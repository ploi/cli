<?php

namespace App\Commands\Server\Database;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class DeleteDatabaseCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'database:delete 
                            {--server= : The server where the database will be deleted}
                            {--database-id= : The ID of the database to delete}';

    protected $description = 'Delete a database';

    protected array $server = [];

    public function handle()
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        try {
            $databaseId = $this->option('database-id');
            if (empty($databaseId)) {
                $databases = $this->ploi->databaseList($serverId)['data'];
                if (empty($databases)) {
                    error('No databases found on the selected server.');

                    return 1;
                }

                $databaseId = select(
                    label: 'Select the database to delete:',
                    options: array_column($databases, 'name', 'id'),
                    validate: fn ($value) => ! empty($value) ? null : 'Database selection is required.',
                );
            }

            spin(
                callback: fn () => $this->ploi->deleteDatabase($serverId, $databaseId),
                message: 'Deleting database...',
            );

            $this->success('Database deleted successfully.');

        } catch (\Exception $e) {
            error('An error occurred while deleting the database: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
