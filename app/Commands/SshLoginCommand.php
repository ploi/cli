<?php

namespace App\Commands;

use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\text;

class SshLoginCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'ssh {--server=} {--site=} {--u|user=}';

    protected $description = 'Login to your server via SSH';

    protected array $server = [];

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();

        $this->server = $this->ploi->getServerDetails($serverId)['data'];
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        $user = $this->determineUser();
        $user = text(
            label: 'Enter the username you want to login with',
            default: $user,
            required: true
        );

        $this->info("Logging in to {$this->server['name']} as {$user}");

        Process::forever()->tty()->run("ssh {$user}@{$this->server['ip_address']}");
    }

    protected function determineUser(): string
    {
        if ($this->option('user')) {
            return $this->option('user');
        }

        if (! empty($this->site)) {
            return $this->site['system_user'];
        }

        return text(label: 'Enter the username you want to login with', default: 'ploi');
    }
}
