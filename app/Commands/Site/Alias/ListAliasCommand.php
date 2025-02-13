<?php

namespace App\Commands\Site\Alias;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\confirm;

class ListAliasCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'alias:list {--server=} {--site=}';

    protected $description = 'Get all aliases for a site';

    protected array $site;

    protected array $server;

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        $alias = $this->ploi->getAliases($serverId, $siteId)['data'];

        if (empty($alias['aliases'])) {
            $this->warn("No aliases found for site {$alias['main']}.");

            if (confirm('Would you like to create a alias?', 'yes')) {
                $this->call('alias:create', [
                    '--server' => $this->option('server') ?? $this->server['name'],
                    '--site' => $this->option('site') ?? $this->site['domain'],
                ]);
            }

            return;
        }

        $this->line("Found {$alias['count']} alias for site {$alias['main']}:");

        $headers = ['Alias'];
        $rows = collect($alias['aliases'])->map(fn ($alias) => [
            $alias,
        ])->toArray();

        $this->table($headers, $rows);
    }
}
