<?php

namespace App\Commands\Site;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class ResetSitePermissions extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'site:reset-permissions {--server=} {--site=}';

    protected $description = 'Resets site permissions for your site if owner has changed';

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        $this->ploi->resetSitePermissions($serverId, $siteId)['data'];
        $this->success('Site permissions are being reset in the background');

    }
}
