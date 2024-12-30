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

class DatabaseCreateCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'database:create 
                            {--server= : The server where the database will be created}
                            {--name= : The name of the database (alpha-numeric, dashes, underscores, 2-64 characters)}
                            {--user= : The database user (optional, alpha-numeric, dashes, underscores, 2-64 characters)}
                            {--password= : The database password (optional)}
                            {--description= : A description for the database (optional)}
                            {--site_id= : The site ID to associate with the database (optional)}';

    protected $description = 'Create a database';

    protected array $server = [];

    public function handle()
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        try {
            $name = $this->option('name') ?? text(
                label: 'Enter the database name:',
                placeholder: 'E.g. my_database',
                validate: fn ($value) => preg_match('/^[a-zA-Z0-9_-]{2,64}$/', $value) ? null : 'Invalid database name format.',
                hint: 'Alpha-numeric characters, dashes, and underscores only (2-64 characters).'
            );

            $user = $this->option('user') ?? text(
                label: 'Enter the database user (optional):',
                placeholder: 'E.g. db_user',
                validate: fn ($value) => empty($value) || preg_match('/^[a-zA-Z0-9_-]{2,64}$/', $value) ? null : 'Invalid user format.',
                hint: 'Alpha-numeric characters, dashes, and underscores only (2-64 characters).'
            );

            $password = $this->option('password') ?? password(
                label: 'Enter the database password (optional):',
                placeholder: 'Type your password',
                hint: 'Leave empty to skip.'
            );

            $description = $this->option('description') ?? text(
                label: 'Enter a description for the database (optional):',
                placeholder: 'E.g. My first database'
            );

            $siteId = $this->option('site_id') ?? null;
            if (! $siteId) {
                $linkSite = confirm(
                    label: 'Would you like to link a site to this database? (yes/no)',
                    default: false
                );

                if ($linkSite) {
                    $sites = collect($this->ploi->getSiteList($serverId)['data'])->pluck('domain', 'id')->toArray();
                    $siteId = select(
                        label: 'Select a site to associate with the database:',
                        options: $sites
                    );
                }
            }

            spin(
                callback: fn () => $this->ploi->createDatabase($serverId, [
                    'name' => $name,
                    'user' => $user ?: null,
                    'password' => $password ?: null,
                    'description' => $description ?: null,
                    'site_id' => $siteId ?: null,
                ]),
                message: 'Creating new database...'
            );

            $this->success('Database created successfully.');

        } catch (\Exception $e) {
            error('An error occurred while creating the database: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
