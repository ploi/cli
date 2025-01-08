<?php

namespace App\Commands\Server\Database;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithDatabase;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\error;
use function Laravel\Prompts\password;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class CreateDatabaseUserCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithDatabase, InteractWithServer, InteractWithSite;

    protected $signature = 'database:create-user 
                            {--server= : The server where the database will be created}
                            {--database= : The database to add the user to}
                            {--user= : The database user (alpha-numeric, no dashes, 2-64 characters)}
                            {--password= : The database password}
                            {--remote : If the user should be remote accessible (optional)}
                            {--remote_ip= : The IP address this user connects from (optional, required if remote is true), or % for any IP}
                            {--readonly : If the created user should only have readonly rights (optional)}';

    protected $description = 'Create a database user';

    protected array $server = [];

    protected array $database = [];

    public function handle()
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        try {
            $database = $this->getDatabase();

            $user = $this->option('user') ?? text(
                label: 'Enter the database user:',
                placeholder: 'E.g. db_user',
                required: true,
                validate: fn ($value) => preg_match('/^[a-zA-Z0-9_]{2,64}$/', $value) ? null : 'Invalid user format.',
                hint: 'Alpha-numeric characters, no dashes (2-64 characters).'
            );

            $password = $this->option('password') ?? password(
                label: 'Enter the database password:',
                placeholder: 'Type your password',
                required: true,
            );

            $remote = $this->option('remote') ?? false;
            $remoteIp = $this->option('remote_ip') ?? null;
            if ($remote) {
                $remoteIp = text(
                    label: 'Enter the IP address this user connects from, or % for any IP:',
                    placeholder: 'E.g. 192.168.1.1 or %',
                    required: true,
                    validate: fn ($value) => filter_var($value, FILTER_VALIDATE_IP) || $value === '%' ? null : 'Invalid IP address format.',
                );
            }

            $readonly = $this->option('readonly') ?? false;

            $userDetails = [
                'user' => $user,
                'password' => $password,
                'remote' => $remote ?? null,
                'readonly' => $readonly ?? null,
            ];

            if ($remote) {
                $userDetails['remote_ip'] = $remoteIp ?? null;
            }

            spin(
                callback: fn () => $this->ploi->createDatabaseUser($serverId, $database, $userDetails),
                message: 'Creating new database user...'
            );

            $this->success('Database user created successfully.');

        } catch (\Exception $e) {
            error('An error occurred while creating the database user: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
