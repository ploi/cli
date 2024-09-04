<?php

namespace App\Commands\v1;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Support\Configuration;
use App\Support\Ploi;
use LaravelZero\Framework\Commands\Command;

class SshCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'ssh {user?}';

    protected $description = 'Start a new SSH session.';

    public function handle(Ploi $ploi, Configuration $configuration)
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $server = $ploi->getServer($configuration->get('server'));
        $site = $ploi->getSite($configuration->get('server'), $configuration->get('site'));
        $user = $this->argument('user') ?? 'ploi';

        $this->info('Establishing SSH connection...');

        passthru($this->buildCommand($server, $site, $user));
    }

    protected function buildCommand($server, $site, $user)
    {
        return sprintf('ssh -t %s@%s "cd %s; bash --login"', $user, $server['ip_address'], $site['domain'] . $site['project_root']);
    }
}
