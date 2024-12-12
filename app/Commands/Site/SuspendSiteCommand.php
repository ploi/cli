<?php

namespace App\Commands\Site;

use App\Commands\Command as BaseCommand;
use App\Traits\EnsureHasToken;
use App\Traits\HasRepo;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class SuspendSiteCommand extends BaseCommand
{
    use EnsureHasToken, HasRepo;

    protected $signature = 'suspend:site {--server=} {--site=} {reason?}';

    protected $description = 'Suspend a website in your server';

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->option('server');
        $siteId = $this->option('site');

        if (! $serverId || ! $siteId) {
            $serverId = $this->selectServer();
        } else {
            $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];
        }

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

    protected function selectServer(): int|string
    {
        if ($this->ploi->getServerList()['data'] === null) {
            $this->error('No servers found! Please create a server first.');
            exit(1);
        }

        $servers = collect($this->ploi->getServerList()['data'])->pluck('name', 'id')->toArray();

        return select('Select a server:', $servers);
    }

    protected function selectSite($serverId): array
    {
        $sites = collect($this->ploi->getSiteList($serverId)['data'])->pluck('domain', 'id')->toArray();
        $siteId = select('On which site you want to install the repository?', $sites);

        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        return ['id' => $siteId, 'domain' => $sites[$siteId]];
    }
}
