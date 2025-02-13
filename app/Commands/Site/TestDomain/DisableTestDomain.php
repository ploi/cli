<?php

namespace App\Commands\Site\TestDomain;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\confirm;

class DisableTestDomain extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'test-domain:disable {--server=} {--site=}';

    protected $description = 'Disable the testing domain for your site';

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        if (! $this->site['test_domain']) {
            $this->warn("{$this->site['domain']} does not have a test domain enabled!");
            $enable = confirm('Do you want to enable it?');
            if ($enable) {
                $data = $this->ploi->enableTestDomain($serverId, $siteId)['data'];
                $this->success('Test domain has been enabled!');
                if (isset($data['test_domain_url'])) {
                    $this->line($data['test_domain_url']);
                }
            }

            return;
        }

        $this->ploi->disableTestDomain($serverId, $siteId)['data'];
        $this->success('Test domain has been disabled!');

    }
}
