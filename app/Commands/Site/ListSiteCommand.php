<?php

namespace App\Commands\Site;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

class ListSiteCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'site:list {--server=}';

    protected $description = 'Get all sites on a server';

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();
        $sites = $this->ploi->getSiteList($serverId)['data'];

        //             "id": 1,
        //            "status": "active",
        //            "server_id": 146,
        //            "domain": "domain1.com",
        //            "deploy_script": false,
        //            "web_directory": "/public",
        //            "project_type": "wordpress",
        //            "project_root": "/",
        //            "last_deploy_at": null,
        //            "system_user": "ploi",
        //            "php_version": "7.2",
        //            "health_url": null,
        //            "notification_urls": {
        //                "slack": null,
        //                "discord": null,
        //                "webhook": null
        //            },
        //            "has_repository": false,
        //            "created_at": "2019-07-29 10:27:30"
        $headers = ['ID', 'Server', 'Domain', 'Project type', 'Last deploy at', 'PHP version', 'Has repository'];
        $rows = collect($sites)->map(fn ($site) => [
            $site['id'],
            $site['server_id'],
            $site['domain'],
            $site['project_type'] ?? 'None (Static HTML or PHP)',
            $site['last_deploy_at'] ?? 'Never',
            $site['php_version'],
            $site['has_repository'] ? 'Yes' : 'No',
        ])->toArray();

        $this->table($headers, $rows);
    }
}
