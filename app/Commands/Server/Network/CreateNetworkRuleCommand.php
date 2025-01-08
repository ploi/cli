<?php

namespace App\Commands\Server\Network;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\form;

class CreateNetworkRuleCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'network-rule:create {--server=} {--name=} {--port=} {--type=} {--rule_type=} {--from_ip_address=}';

    protected $description = 'Create a network rule on a server';

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        $responses = form()
            ->text(label: 'What is the name of the network rule?', required: true, name: 'name')
            ->text(label: 'What is the port?', required: true, validate: fn (string $value) => match (true) {
                ! preg_match('/^([0-9]{0,5})(:[0-9]{0,5})?$/', $value) => 'The port must be a valid number or range (e.g., 5000:6000).',default => null,
            }, hint: 'You may enter a port range e.g. 5000:6000', name: 'port')
            ->select(label: 'What is the type?', options: ['tcp' => 'TCP', 'udp' => 'UDP'], default: 'tcp', name: 'type')
            ->select(label: 'What is the rule type?', options: ['allow' => 'Allow', 'deny' => 'Deny'], default: 'allow', name: 'rule_type')
            ->text(label: 'From IP address', hint: "Optional: You may enter an IP range to allow multiple IP's e.g. 10.0.0.0/24, you may also define multiple IP addresses by separating them with a comma.", name: 'from_ip_address')
            ->submit();

        $rule = $this->ploi->createNetworkRule($serverId, $responses)['data'];

        if ($rule['status'] === 'error') {
            $this->error($rule['message']);
            exit(1);
        }

        $this->success('Network rule created successfully.');

    }
}
