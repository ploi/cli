<?php

namespace App\Commands\Server;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

class LogsServerCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'server:logs {logid?} {--server=}';

    protected $description = 'Get the logs of a server';

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        $logId = $this->argument('logid');

        $logId ? $this->displaySingleLog($serverId, $logId) : $this->displayMultipleLogs($serverId);
    }

    protected function displaySingleLog($serverId, $logId): void
    {
        $log = $this->ploi->getServerLog($serverId, $logId)['data'];

        $this->info("Description: {$log['description']}");
        $this->info('Type: '.($log['type'] ?? 'N/A'));
        $this->info("Created At: {$log['created_at']}");
        $this->line("Content: {$log['content']}");
    }

    protected function displayMultipleLogs($serverId): void
    {
        $logs = $this->ploi->getServerLogs($serverId)['data'];

        $headers = ['ID', 'Description', 'Type', 'Created At'];
        $rows = collect($logs)->map(fn ($log) => [
            $log['id'],
            $log['description'],
            $log['type'] ?? 'N/A',
            $log['created_at'],
        ])->toArray();

        $this->table($headers, $rows);
    }
}
