<?php

namespace App\Commands\Site\Env;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;

class EnvPullCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'env:pull {--server=} {--site=} {--file=}';

    protected $description = 'Pull the environment file from a site';

    protected array $site = [];

    public function handle()
    {
        $this->ensureHasToken();

        $this->hasPloiConfiguration();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        try {
            $env = $this->ploi->getEnv($serverId, $siteId)['data'];

            if (is_null($this->option('file')) && File::exists($this->option('file') ?? '.env') && ! confirm('Environment file already exists. Do you want to overwrite it?', default: false)) {
                return 0;
            }

            File::put($this->option('file') ?? '.env', $env);
            $this->success("Environment file for {$this->site['domain']} has been pulled successfully.");

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
