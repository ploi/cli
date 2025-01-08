<?php

namespace App\Commands\Server\Network;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class DeleteNetworkRuleCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'network-rule:delete {--server=} {--rule-id= : The ID of the network rule to delete}';

    protected $description = 'Delete a network rule on a server';

    protected array $server = [];

    public function handle()
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        try {
            $networkRuleId = $this->option('rule-id');
            if (empty($networkRuleId)) {
                $rules = $this->ploi->getNetworkRules($serverId)['data'];
                if (empty($rules)) {
                    error('No network rules found on the selected server.');

                    return 1;
                }

                $networkRuleId = select(
                    label: 'Select the network rule to delete:',
                    options: collect($rules)->mapWithKeys(fn ($rule) => [$rule['id'] => $rule['name'].' ('.$rule['rule_type'].') '.$rule['port']])->toArray(),
                    validate: fn ($value) => ! empty($value) ? null : 'Network rule selection is required.',
                );
            }

            spin(
                callback: fn () => $this->ploi->deleteNetworkRule($serverId, $networkRuleId),
                message: 'Deleting network rule...',
            );

            $this->success('Network rule deleted successfully.');

        } catch (\Exception $e) {
            error('An error occurred while deleting the network rule: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
