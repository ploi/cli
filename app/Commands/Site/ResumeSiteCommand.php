<?php

namespace App\Commands\Site;

use App\Commands\Command as BaseCommand;
use App\Traits\EnsureHasPloiConfiguration;
use App\Traits\EnsureHasToken;
use App\Traits\HasRepo;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class ResumeSiteCommand extends BaseCommand
{
    use EnsureHasPloiConfiguration, EnsureHasToken, HasRepo;

    protected $signature = 'resume:site {--server=} {--site=}';

    protected $description = 'Resume a suspended website in your server';

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $serverId = $this->option('server');
        $siteId = $this->option('site');

        if (! $serverId || ! $siteId) {
            $serverId = $this->selectServer();
        } else {
            $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];
        }

        $this->ploi->resumeSite($serverId, $this->site['id']);
        $this->info("{$this->site["domain"]} has been resumed!");
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
