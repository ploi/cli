<?php

namespace App\Commands\User;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\UserService;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\text;

class UserServerProviderCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'user:server-provider {--id= : The id of the server provider}';
    protected $description = 'Get a specific server provider in your account, so you can create a server with these details';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $providerId = $this->option('id');

        if(!$providerId){
            $providerId = text(
                label: 'What is the id of the server provider?',
                required: 'ID is required.',
            );
        }

        $userService = new UserService();
        $serverProvider = $userService->serverProviders($providerId)->getData();

        $this->line('Server Provider Information of ' . $serverProvider->label);

        $this->table([
            'ID',
            'Plan',
        ], collect($serverProvider->provider->plans)->map(fn($serverProvider) => [
            'id'   => $serverProvider->id,
            'plan' => $serverProvider->description,
        ]));

        $this->newLine();
        $this->line('Server Provider Regions');
        $this->table([
            'ID',
            'Region',
        ], collect($serverProvider->provider->regions)->map(fn($serverProvider) => [
            'id'     => $serverProvider->id,
            'region' => $serverProvider->name,
        ]));
    }
}
