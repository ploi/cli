<?php

namespace App\Commands\Site\Env;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\spin;

class EnvPushCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'env:push {--server=} {--site=} {--file=}';

    protected $description = 'Push the environment file to a site';

    protected array $site = [];

    public function handle()
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        try {

            spin(
                function () use ($serverId, $siteId) {

                    $this->ploi->updateEnv($serverId, $siteId, [
                        'content' => File::get($this->option('file') ?? '.env'),
                    ]);

                },
                'Pushing the environment file...'
            );

            $this->success("Environment file for {$this->site['domain']} has been pushed successfully.");

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
