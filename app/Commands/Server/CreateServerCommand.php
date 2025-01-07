<?php

namespace App\Commands\Server;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithUser;
use App\Traits\EnsureHasToken;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\form;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class CreateServerCommand extends Command
{
    use EnsureHasToken, InteractWithUser;

    protected $signature = 'server:create {--custom}';

    protected $description = 'Create a server';

    protected array $serverProviders = [];

    // server, load-balancer, database-server or redis-server
    protected static array $server_types = [
        'server' => 'Server',
        'load-balancer' => 'Load Balancer',
        'database-server' => 'Database Server',
        'redis-server' => 'Redis Server',
    ];

    protected static array $database_types = [
        'none' => 'Do not install a database',
        'mysql' => 'MySQL',
        'mariadb' => 'MariaDB',
        'postgresql' => 'PostgreSQL',
        'postgresql13' => 'PostgreSQL 13',
    ];

    protected static array $os_types = [
        'ubuntu-24-04-lts' => 'Ubuntu 24.04 LTS',
        'ubuntu-22-04-lts' => 'Ubuntu 22.04 LTS',
        'ubuntu-20-04-lts' => 'Ubuntu 20.04 LTS',
        'ubuntu-18-04-lts' => 'Ubuntu 18.04 LTS',
    ];

    protected static array $php_versions = [
        'none' => 'Do not install PHP',
        '8.4' => '8.4',
        '8.3' => '8.3',
        '8.2' => '8.2',
        '8.1' => '8.1',
        '8.0' => '8.0',
        '7.4' => '7.4',
        '7.3' => '7.3',
        '7.2' => '7.2',
        '7.1' => '7.1',
        '7.0' => '7.0',
    ];

    public function handle(): void
    {
        $this->ensureHasToken();

        $this->serverProviders = $this->ploi->getProviders()['data'];

        try {
            if ($this->option('custom')) {
                $this->createCustomServer();
            } else {
                $this->createServer();
            }

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function createServer(): void
    {
        $responses = form()
            ->text(
                label: 'What should be the name of the server?',
                validate: fn (string $value) => $value === '' || preg_match('/^[a-z0-9-_]+$/i', $value)
                    ? null
                    : 'The server name may only contain letters, numbers, dashes, and underscores.',
                hint: 'If left empty, a random name will be used.',
                name: 'name'
            )
            ->select(
                label: 'Which provider should be used?',
                options: collect($this->serverProviders)
                    ->mapWithKeys(fn ($provider) => [
                        $provider['id'] => $provider['label'].' - '.$provider['name'],
                    ])
                    ->toArray(),
                name: 'credential'
            )
            ->add(function ($responses) {
                $provider = collect($this->serverProviders)
                    ->firstWhere('id', $responses['credential']);

                return select(
                    label: 'Which plan should be used?',
                    options: collect($provider['provider']['plans'])
                        ->mapWithKeys(fn ($plan) => [
                            $plan['id'] => $plan['description'],
                        ])
                        ->toArray()
                );
            }, name: 'plan')
            ->add(function ($responses) {
                $provider = collect($this->serverProviders)
                    ->firstWhere('id', $responses['credential']);

                return select(
                    label: 'Which region should be used?',
                    options: collect($provider['provider']['regions'])
                        ->mapWithKeys(fn ($region) => [
                            $region['id'] => $region['name'],
                        ])
                        ->toArray()
                );
            }, name: 'region')
            ->select(
                label: 'Which type of server should be created?',
                options: self::$server_types,
                name: 'type'
            )
            ->select(
                label: 'Which operating system should be installed?',
                options: self::$os_types,
                name: 'os_type'
            )
            ->add(function ($responses) {
                if ($responses['type'] === 'server') {
                    return select(
                        label: 'Which PHP version should be installed?',
                        options: self::$php_versions
                    );
                }

                return null;
            }, name: 'php_version')
            ->add(function ($responses) {
                if (in_array($responses['type'], ['server', 'database-server'])) {
                    $databaseOptions = $responses['type'] === 'database-server'
                        ? array_filter(self::$database_types, fn ($key) => $key !== 'none', ARRAY_FILTER_USE_KEY)
                        : self::$database_types;

                    return select(
                        label: 'Which database should be installed?',
                        options: $databaseOptions,
                        validate: fn ($value) => $responses['type'] === 'database-server' && $value === 'none'
                            ? 'You must select a database type for a Database Server.'
                            : null
                    );
                }

                return null;
            }, name: 'database_type')
            ->text(
                label: 'Additional description for your server as note',
                name: 'description'
            )
            ->add(function () {
                $user = $this->getUserDetails();
                if ($user['plan'] === 'Pro' || $user['plan'] === 'Unlimited') {
                    return confirm(
                        label: 'Install Ploi monitoring?',
                        hint: 'Requires Pro plan or higher',
                    );
                }

                return null;
            }, name: 'install_monitoring')
            ->add(function () {
                $install = confirm(
                    label: 'Install a webhook URL?',
                    default: false,
                    hint: 'This URL gets triggered by Ploi when installation has completed.',
                );

                if ($install) {
                    return text(
                        label: 'What is the URL that should be triggered?',
                        required: true,
                        hint: 'This URL gets triggered by Ploi when installation has completed.',
                    );
                }

                return null;
            }, name: 'webhook_url')
            ->submit();

        $responses['webserver_type'] = 'nginx';

        $server = $this->ploi->createServer(array_filter($responses))['data'];
        $this->info('Server creation initiated...');
        $this->pollServerStatus($server['id']);

    }

    public function createCustomServer() {}

    protected function pollServerStatus(string $serverId): void
    {
        $maxAttempts = 100;  // Maximum number of polling attempts per status
        $delay = 15;        // Delay between each attempt in seconds

        $this->info('Server is being created!');
        $isCreated = spin(
            callback: function () use ($serverId, $maxAttempts, $delay) {
                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    $status = $this->ploi->getServerDetails($serverId)['data']['status'];
                    if ($status === 'building') {
                        return true;
                    }
                    sleep($delay);
                }

                return false;
            },
            message: 'Waiting for the server to be created...'
        );

        if (! $isCreated) {
            $this->error('Server creation timed out.');

            return;
        }

        $this->info('Server is being built!');
        $isBuilding = spin(
            callback: function () use ($serverId, $maxAttempts, $delay) {
                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    $status = $this->ploi->getServerDetails($serverId)['data']['status'];
                    if ($status === 'active') {
                        return true;
                    }
                    sleep($delay);
                }

                return false;
            },
            message: 'Waiting for the server to finish building...'
        );

        if (! $isBuilding) {
            $this->error('Server building timed out.');

            return;
        }


        $this->info('Server is ready!');
    }
}
