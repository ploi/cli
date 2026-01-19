<?php

namespace App\Commands;

use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\confirm;

class ProvisionCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'provision';

    protected $description = 'Command description';

    public function handle()
    {
        $this->warn('This command is still WIP, will be finished very soon');

        return;

        $this->ensureHasToken();

        if (! $this->hasPloiProvisionFile()) {
            $this->warn('You do not own a provision.yml file in your .ploi folder. Please create this file.');
        }

        $this->info('Starting up server creation via the provision.yml file');

        $provision = $this->configuration->get('provision');

        $checkServer = collect($this->ploi->getServerList(search: $provision['server']['name'])['data'])
            ->first(fn ($server) => $server['name'] === $provision['server']['name'] || $server['ip_address'] === $provision['server']['name']);

        // If we already have the server, we should skip.
        if ($checkServer) {
            $this->warn('This server already exists, aborting.');
            exit(0);
        }

        $this->success('This server does not exist yet, we can continue with creating!');

        $this->newLine();

        $this->info('Server details:');
        $this->table(['Name', 'Plan', 'Region'], [
            [$provision['server']['name'], $provision['server']['plan'], $provision['server']['region']],
        ]);

        $confirm = confirm('Are you satisfied with the server details? (Up next domains)', false);

        if (! $confirm) {
            exit(0);
        }

        $this->info('Domain details:');
        $this->table(['Domain', 'System user', 'Aliases'], collect($provision['domains'])->map(fn ($domain) => [
            'domain' => $domain['root'],
            'system_user' => $domain['system_user'],
            'aliases' => implode(' ', $domain['aliases']),
        ])->toArray());

        $confirm = confirm('Are you satisfied with the domain details?', false);

        if (! $confirm) {
            exit(0);
        }

        $server = $this->ploi->createServer($provision['server'])['data'];
        $this->info('Server creation initiated...');

        $done = $this->pollServerStatus($server['id']);

        if ($done) {
            //
        }
    }
}
