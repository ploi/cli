<?php

namespace App\Commands\User;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\UserService;
use LaravelZero\Framework\Commands\Command;

class UserServerProvidersCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'user:server-providers';
    protected $description = 'List all the server providers for you account, so you can create a server with these details';

    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $userService = new UserService();
        $serverProviders = $userService->serverProviders()->getData();

        $this->table([
            'ID',
            'Label',
            'Name',
            'Created At'
        ], collect($serverProviders)->map(fn($serverProvider) => [
            'id'         => $serverProvider->id,
            'label'      => $serverProvider->label,
            'name'       => $serverProvider->name,
            'created_at' => $serverProvider->created_at,
        ]));
    }
}
