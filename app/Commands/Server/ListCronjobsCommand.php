<?php

namespace App\Commands\Server;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

class ListCronjobsCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'cronjobs {--server=}';

    protected $description = 'Get the cronjobs on a server';

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        $cronjobs = $this->ploi->getCronjobs($serverId)['data'];

        $headers = ['ID', 'Status', 'Command', 'User', 'Frequency', 'Created At'];
        $rows = collect($cronjobs)->map(fn ($cronjob) => [
            $cronjob['id'],
            $cronjob['status'],
            $cronjob['command'],
            $cronjob['user'],
            $cronjob['frequency'],
            $cronjob['created_at'],
        ])->toArray();

        $this->table($headers, $rows);
    }
}
