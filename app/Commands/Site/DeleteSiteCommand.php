<?php

namespace App\Commands\Site;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class DeleteSiteCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'site:delete {--server=} {--site=} {--force}';

    protected $description = 'Delete a site';

    protected array $server = [];

    public function handle()
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        $this->warn('!! This action is irreversible !!');
        $this->warn('!! All data will be deleted !!');

        $confirm = $this->option('force') || text(
            label: 'Type the site name to confirm deletion: '.$this->site['domain'],
            validate: fn (string $value) => match (true) {
                $value !== $this->site['domain'] => 'The site name does not match.',
                default => null,
            }
        );

        if (! $confirm) {
            $this->info('Site deletion aborted.');

            return 0;
        }

        try {
            spin(
                callback: fn () => $this->ploi->deleteSite($serverId, $siteId),
                message: 'Deleting site...',
            );

            $this->success('Site deleted successfully.');

        } catch (\Exception $e) {
            error('An error occurred while deleting the site: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
