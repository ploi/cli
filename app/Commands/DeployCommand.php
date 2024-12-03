<?php

namespace App\Commands;

use App\Traits\EnsureHasPloiConfiguration;
use App\Traits\EnsureHasToken;

class DeployCommand extends Command
{
    use EnsureHasPloiConfiguration, EnsureHasToken;

    protected $signature = 'ploi deploy';

    protected $description = 'Deploy your site to Ploi.io.';

    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $this->info('Deploying your site to Ploi.io...');
    }
}
