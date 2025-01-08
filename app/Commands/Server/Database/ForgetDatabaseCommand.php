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

class ForgetDatabaseCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'database:forget 
                            {--server= : The server where the database will be acknowledged}
                            {--database_id= : The ID of the database to forget}}';

    protected $description = 'Forget a database that has been removed outside of Ploi';

    protected array $server = [];

    public function handle()
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        $databaseList = collect($this->ploi->databaseList($serverId)['data'])->pluck('name', 'id')->toArray();

        try {

            $databaseId = $this->option('database_id') ?? select(
                label: 'Select a database to forget:',
                options: $databaseList,
            );

            spin(
                callback: fn () => $this->ploi->forgetDatabase($serverId, $databaseId),
                message: 'Forgetting database...',
            );

            $this->success('Database forgotten successfully.');

        } catch (\Exception $e) {
            error('An error occurred while forgetting the database: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
