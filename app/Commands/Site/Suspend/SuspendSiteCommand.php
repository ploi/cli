<?php

namespace App\Commands\Site\Suspend;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class SuspendSiteCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'site:suspend {--server=} {--site=} {reason?}';

    protected $description = 'Suspend a website in your server';

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        if ($this->site['status'] === 'suspended') {
            $this->warn("{$this->site['domain']} is already suspended!");
            $resume = confirm('Do you want to unsuspend it?');
            if ($resume) {
                $this->ploi->resumeSite($serverId, $this->site['id']);
                $this->info("{$this->site['domain']} has been resumed!");
            }
            exit(1);
        }

        $reason = $this->argument('reason') ?? text(
            label: 'Why do you want to suspend this site?',
            hint: 'You can specify a reason which will be displayed on the suspended template.'
        );

        $this->ploi->suspendSite($serverId, $this->site['id'], ['reason' => $reason]);
        $this->info("{$this->site['domain']} has been suspended!");
    }
}
