<?php

namespace App\Commands\Site\Repository;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class DeleteRepoCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'repository:delete {--server=} {--site=}';

    protected $description = 'Delete a repository connected to your site';

    protected array $server = [];

    protected array $site = [];

    public function handle()
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        try {
            spin(
                callback: fn () => $this->ploi->deleteRepository($serverId, $siteId),
                message: 'Deleting repository...',
            );

            $this->success('Repository deleted successfully.');

        } catch (\Exception $e) {
            error('An error occurred while deleting the repository: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
