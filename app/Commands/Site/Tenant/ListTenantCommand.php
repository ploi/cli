<?php

namespace App\Commands\Site\Tenant;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\confirm;

class ListTenantCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'tenant:list {--server=} {--site=}';

    protected $description = 'Get all tenants for a site';

    protected array $site;

    protected array $server;

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        $tenants = $this->ploi->getTenants($serverId, $siteId)['data'];

        if (empty($tenants['tenants'])) {
            $this->warn("No tenants found for site {$tenants['main']}.");

            if (confirm('Would you like to create a tenant?', 'yes')) {
                $this->call('tenant:create', [
                    '--server' => $this->option('server') ?? $this->server['name'],
                    '--site' => $this->option('site') ?? $this->site['domain'],
                ]);
            }

            return;
        }

        $this->line("Found {$tenants['count']} tenants for site {$tenants['main']}:");

        $headers = ['Tenants'];
        $rows = collect($tenants['tenants'])->map(fn ($tenant) => [
            $tenant,
        ])->toArray();

        $this->table($headers, $rows);
    }
}
