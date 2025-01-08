<?php

namespace App\Commands\Server\Daemons;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\form;

class CreateDaemonCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'daemon:create {--server=} {--command=} {--system-user=} {--processes=} {--directory=}';

    protected $description = 'Create a daemon on a server';

    protected array $server = [];

    protected array $systemUsers = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();
        $fetchedUsers = $this->systemUsers()['data'];

        $this->server = $this->ploi->getServerDetails($serverId)['data'];
        $this->systemUsers = array_merge(
            [
                ['id' => 'ploi', 'name' => 'ploi (default)'],
                ['id' => 'root', 'name' => 'root'],
            ],
            $fetchedUsers
        );

        // Ensure no duplicate entries for ploi or root (just in case, you never know)
        $this->systemUsers = collect($this->systemUsers)
            ->unique('id')
            ->values()
            ->toArray();

        if ($this->option('command') && $this->option('system-user') && $this->option('processes') && $this->option('directory')) {
            $responses = [
                'command' => $this->option('command'),
                'system_user' => $this->option('system-user'),
                'processes' => $this->option('processes'),
                'directory' => $this->option('directory'),
            ];
        } else {
            $responses = form()
                ->text(
                    label: 'What command should the daemon run?',
                    required: true,
                    validate: fn ($value) => strlen($value) <= 150 ? null : 'The command cannot exceed 150 characters.',
                    name: 'command'
                )
                ->select(
                    label: 'Select a system user:',
                    options: collect($this->systemUsers)->mapWithKeys(fn ($user) => [$user['id'] => $user['name']])->toArray(),
                    name: 'system_user'
                )
                ->text(
                    label: 'How many processes should the daemon spawn?',
                    validate: fn ($value) => filter_var($value, FILTER_VALIDATE_INT) && $value > 0 ? null : 'Please enter a positive integer.',
                    name: 'processes'
                )
                ->text(
                    label: 'What directory should the daemon use?',
                    validate: fn ($value) => str_starts_with($value, '/') ? null : 'The directory must start with a forward slash.',
                    name: 'directory'
                )
                ->submit();
        }

        $daemon = $this->ploi->createDaemon($serverId, $responses)['data'];

        if (empty($daemon)) {
            $this->error('Daemon could not be created. Check the input and try again.');
            exit(1);
        }

        $this->success('Daemon created successfully.');
    }

}
