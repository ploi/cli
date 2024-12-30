<?php

namespace App\Commands\Server;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class DatabaseAcknowledgeCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'database:acknowledge 
                            {--server= : The server ID where the database will be acknowledged}
                            {--name= : The name of the database (alpha-numeric, dashes, underscores, 2-64 characters)}';

    protected $description = 'Acknowledge a database';

    protected array $server = [];

    public function handle()
    {
        $this->ensureHasToken();

        $serverId = $this->option('server') ?? $this->getServerId();
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        try {
            $name = $this->option('name') ?? text(
                label: 'Enter the database name:',
                placeholder: 'E.g. my_database',
                validate: fn ($value) => preg_match('/^[a-zA-Z0-9_-]{2,64}$/', $value) ? null : 'Invalid database name format.',
                hint: 'Alpha-numeric characters, dashes, and underscores only (2-64 characters).'
            );

            spin(
                callback: fn () => $this->ploi->acknowledgeDatabase($serverId, [
                    'name' => $name,
                ]),
                message: 'Acknowledging database creation...',
            );

            $this->success('Database acknowledged successfully.');

        } catch (\Exception $e) {
            error('An error occurred while acknowledging the database: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
