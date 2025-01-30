<?php

namespace App\Commands;

use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

class ProvisionCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'provision';
    protected $description = 'Command description';

    public function handle()
    {
        $this->ensureHasToken();

        if (!$this->hasPloiProvisionFile()) {
            $this->warn("You do not own a provision.yml file in your .ploi folder. Please create this file.");
        }

        $provision = $this->configuration->get('provision');

        $server = $this->getServerIdByNameOrIp($provision['server']['name']);

        // If we already have the server, we should skip.
        if (is_int($server)) {
            $this->warn('This server already exists, aborting.');
            exit(0);
        }
    }
}
