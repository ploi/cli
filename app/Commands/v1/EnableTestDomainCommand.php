<?php

namespace App\Commands\v1;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Support\Configuration;
use App\Support\Ploi;
use LaravelZero\Framework\Commands\Command;

class EnableTestDomainCommand extends Command
{

    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'test:enable';
    protected $description = 'Enables a test domain for your site';

    public function handle(Ploi $ploi, Configuration $configuration)
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $testDomain = $ploi->enableTestDomain($configuration->get('server'), $configuration->get('site'))['full_test_domain'];

        $this->info("✅ Enabled test domain!");
        $this->warn("You can now visit your site under {$testDomain}.");
    }

}
