<?php

namespace App\Commands;

use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\search;

class SshLoginCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'ssh {--server=} {--u|user=}';

    protected $description = 'Login to your server via SSH';

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();
        $server = $this->ploi->getServerDetails($serverId)['data'];

        $user = $this->option('user') ?? $this->selectSystemUser($serverId);

        $this->info("Logging in to {$server['name']} as {$user}");

        Process::forever()->tty()->run("ssh {$user}@{$server['ip_address']}");
    }

    protected function selectSystemUser(int $serverId): string
    {
        $userNames = collect($this->ploi->getSystemUsers($serverId)['data'] ?? [])
            ->pluck('name')
            ->prepend('ploi')
            ->unique()
            ->values();

        $sitesByUser = collect($this->ploi->getSiteList($serverId)['data'] ?? [])
            ->groupBy('system_user');

        $options = $userNames->mapWithKeys(function (string $name) use ($sitesByUser) {
            $domains = $sitesByUser->get($name, collect())
                ->pluck('domain')
                ->implode(', ');

            $label = $domains !== ''
                ? "$name ($domains)"
                : $name;

            return [$name => $label];
        })->toArray();

        return search(
            label: 'Select a system user:',
            options: fn (string $value) => collect($options)
                ->filter(fn ($label) => str_contains(strtolower($label), strtolower($value)))
                ->toArray(),
            scroll: 10
        );
    }
}