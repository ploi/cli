<?php

namespace App\Commands\Site\AuthUser;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\confirm;

class ListAuthUserCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'auth-user:list {--server=} {--site=}';

    protected $description = 'List all the auth users available in site.';

    protected array $site;

    protected array $server;

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        $authUsers = $this->ploi->getAuthUsers($serverId, $siteId)['data'];

        if (empty($authUsers)) {
            $this->warn("No basic auth users found for site {$this->site['domain']}.");

            if (confirm('Would you like to create a auth user?', 'yes')) {
                $this->call('auth-user:create', [
                    '--server' => $this->option('server') ?? $this->server['name'],
                    '--site' => $this->option('site') ?? $this->site['domain'],
                ]);
            }

            return;
        }

        $headers = ['ID', 'Name', 'Path', 'Created At'];
        $rows = collect($authUsers)->map(fn ($authUser) => [
            $authUser['id'],
            $authUser['name'],
            $authUser['path'],
            $authUser['created_at'],
        ])->toArray();

        $this->table($headers, $rows);
    }
}
