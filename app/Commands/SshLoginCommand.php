<?php

namespace App\Commands;

use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\text;

class SshLoginCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'ssh {--server=} {--u|user=}';

    protected $description = 'Login to your server via SSH';

    protected array $server = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        $user = $user ?? text(label: 'Enter the username you want to login with', default: 'ploi');

        $this->info("Logging in to {$this->server['name']} as {$user}");

        Process::forever()->tty()->run("ssh {$user}@{$this->server['ip_address']}");
    }
}
