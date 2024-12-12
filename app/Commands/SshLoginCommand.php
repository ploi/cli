<?php

namespace App\Commands;

use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class SshLoginCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration;

    protected $signature = 'ssh {--s|server=} {--u|user=}';

    protected $description = 'Login to your server via SSH';

    protected array $server = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        $server = $this->option('server');
        $user = $this->option('user');

        if (! $server) {
            $servers = $this->ploi->getServerList()['data'];

            $serverId = select(
                'Select a server:',
                collect($servers)
                    ->mapWithKeys(fn ($server) => [
                        $server['id'] => $server['name'].' ('.$server['ip_address'].')',
                    ])
                    ->toArray()
            );

            $this->server = $this->ploi->getServerDetails(serverId: $serverId)['data'];
        } else {
            $searchServers = $this->ploi->getServerList(search: $server)['data'];

            if (count($searchServers) > 1) {
                $this->error('Multiple servers found! Please specify the server id or server ip address.');
                $serverId = select(
                    'Select a server:',
                    collect($searchServers)
                        ->mapWithKeys(fn ($server) => [
                            $server['id'] => $server['name'].' ('.$server['ip_address'].')',
                        ])
                        ->toArray()
                );

                $this->server = $this->ploi->getServerDetails(serverId: $serverId)['data'];
                exit();
            } else {
                $this->server = $searchServers[0];
            }
        }

        $user = $user ?? text(label: 'Enter the username you want to login with', default: 'ploi');

        $this->info("Logging in to {$this->server['name']} as {$user}");

        Process::forever()->tty()->run("ssh {$user}@{$this->server['ip_address']}");
    }
}
