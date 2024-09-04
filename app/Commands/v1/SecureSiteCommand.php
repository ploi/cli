<?php

namespace App\Commands\v1;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Support\Configuration;
use App\Support\Ploi;
use LaravelZero\Framework\Commands\Command;

class SecureSiteCommand extends Command
{

    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'ssl:enable';
    protected $description = 'Secure a given url';

    public function handle(Ploi $ploi, Configuration $configuration)
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $domains = $this->ask('Which domains you want to secure? (Separate with a ,)', $configuration->get('domain'));

        $ploi->secure($configuration->get('server'), $configuration->get('site'), $domains);

        $this->info("✅ Creating certificates...");
    }
}
