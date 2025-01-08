<?php

namespace App\Commands\Site\Redirect;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

class ListRedirectCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'redirect:list {--server=} {--site=}';

    protected $description = 'Get the redirects';

    protected array $site = [];

    protected array $type = [
        'permanent' => 'Permanent (301)',
        'redirect' => 'Temporary (302)',
    ];

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        $redirects = $this->ploi->getRedirects($serverId, $siteId)['data'];

        $headers = ['ID', 'Status', 'Redirect From', 'Redirect To', 'Type'];
        $rows = collect($redirects)->map(fn ($redirect) => [
            $redirect['id'],
            $redirect['status'],
            $redirect['redirect_from'],
            $redirect['redirect_to'],
            $this->type[$redirect['type']],
        ])->toArray();

        $this->table($headers, $rows);
    }
}
