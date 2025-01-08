<?php

namespace App\Commands\Server\Network;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

class ListNetworkRulesCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'network-rules:list {--server=}';

    protected $description = 'Get the list of network rules for a server';

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        $rules = $this->ploi->getNetworkRules($serverId)['data'];

        $headers = ['ID', 'Name', 'Port', 'From IP Address', 'Rule Type', 'Status', 'Created At'];
        $rows = collect($rules)->map(fn ($rule) => [
            $rule['id'],
            $rule['name'],
            $rule['port'],
            $rule['from_ip_address'],
            $rule['rule_type'],
            $rule['status'],
            $rule['created_at'],
        ])->toArray();

        $this->table($headers, $rows);
    }
}
