<?php

namespace App\Commands\Site\TestDomain;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\spin;

class EnableTestDomain extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'test-domain:enable {--server=} {--site=}';

    protected $description = 'Enable a testing domain for your site';

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        if ($this->site['test_domain']) {
            $this->warn("{$this->site['domain']} already has a test domain enabled!");
            $this->info("Test domain: {$this->site['test_domain']}");
            $disable = confirm('Do you want to disable it?');
            if ($disable) {
                $this->ploi->disableTestDomain($serverId, $siteId)['data'];
                $this->success('Test domain has been disabled!');
            }

            return;
        }

        $this->ploi->enableTestDomain($serverId, $siteId);
        $this->success('Test domain has been enabled! It will take 5 to 10 minutes before it works because of DNS propagation.');

        while (true) {
            try {
                $response = spin(
                    callback: fn () => $this->ploi->getTestDomain($serverId, $siteId),
                    message: 'Fetching test domain...'
                );

                $data = $response['data'];
                if (! empty($data['test_domain'])) {
                    $this->info($data['test_domain']);
                    break;
                }

                sleep(2);
            } catch (\Throwable $e) {
                sleep(2);

                continue;
            }
        }
    }
}
