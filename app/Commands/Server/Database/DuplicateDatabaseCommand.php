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
use function Laravel\Prompts\text;

class DuplicateDatabaseCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'database:duplicate 
                            {--server= : The server where the database will be duplicated}
                            {--database-id= : The ID of the database to duplicate}
                            {--name= : The name of the new database}
                            {--user= : The user of the new database (optional)}
                            {--password= : The password of the new database (optional)}';

    protected $description = 'Duplicate a database';

    protected array $server = [];

    public function handle()
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();
        $databaseId = $this->option('database-id');
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        if (empty($databaseId)) {
            $databases = $this->ploi->databaseList($serverId)['data'];
            if (empty($databases)) {
                error('No databases found on the selected server.');

                return 1;
            }

            $databaseId = select(
                label: 'Select the database to duplicate:',
                options: array_column($databases, 'name', 'id'),
                validate: fn ($value) => ! empty($value) ? null : 'Database selection is required.',
            );
        }

        try {
            $name = $this->option('name') ?? text(
                label: 'Enter the database name:',
                placeholder: 'E.g. my_database',
                required: true,
                validate: fn ($value) => preg_match('/^[a-zA-Z0-9_-]{2,64}$/', $value) ? null : 'Invalid database name format.',
                hint: 'Alpha-numeric characters, dashes, and underscores only (2-64 characters).'
            );

            $user = $this->option('user') ?? text(
                label: 'Enter the database user (optional):',
                placeholder: 'E.g. my_user',
                validate: fn ($value) => empty($value) || preg_match('/^[a-zA-Z0-9_-]{2,64}$/', $value) ? null : 'Invalid database user format.',
                hint: 'Alpha-numeric characters, dashes, and underscores only (2-64 characters).',
            );

            $password = $this->option('password') ?? text(
                label: 'Enter the database password (optional):',
                placeholder: 'E.g. my_password',
                validate: fn ($value) => empty($value) || preg_match('/^[a-zA-Z0-9_-]{2,64}$/', $value) ? null : 'Invalid database password format.',
                hint: 'Alpha-numeric characters, dashes, and underscores only (2-64 characters).',
            );

            spin(
                callback: fn () => $this->ploi->duplicateDatabase($serverId, $databaseId, [
                    'name' => $name,
                    'user' => $user ?? null,
                    'password' => $password ?? null,
                ]),
                message: 'Duplicating database...',
            );

            $this->success('Database duplicated successfully.');

        } catch (\Exception $e) {
            error('An error occurred while duplicating the database: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
