<?php

namespace App\Commands\Site;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;

class ResumeSiteCommand extends BaseCommand
{
    use EnsureHasToken, InteractWithServer, InteractWithSite;

    protected $signature = 'resume:site {--server=} {--site=}';

    protected $description = 'Resume a suspended website in your server';

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        $this->ploi->resumeSite($serverId, $this->site['id']);
        $this->info("{$this->site['domain']} has been resumed!");
    }
}
