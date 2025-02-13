<?php

namespace App\Commands\Server\Insights;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;

class ListInsightsCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'server:insights {--server=} {--fix=} {--ignore=}';

    protected $description = 'List all the insights available in the server. Optionally fix or ignore specific insights using --fix=ID or --ignore=ID';

    public function handle(): void
    {
        $this->ensureHasToken();
        $serverId = $this->getServerId();

        if ($fixId = $this->option('fix')) {
            $this->fixInsight($serverId, $fixId);

            return;
        }

        if ($ignoreId = $this->option('ignore')) {
            $this->ignoreInsight($serverId, $ignoreId);

            return;
        }

        $insights = $this->ploi->getInsights($serverId)['data'];

        $headers = ['ID', 'Status', 'Description', 'Priority', 'Is Fixable', 'Created At'];
        $rows = collect($insights)->map(fn ($insight) => [
            $insight['id'],
            $insight['status'],
            $insight['description'],
            $insight['priority'],
            $insight['is_fixable'] ? 'Yes' : 'No',
            $insight['created_at'],
        ])->toArray();

        $this->table($headers, $rows);

        $fixableInsights = collect($insights)
            ->filter(fn ($insight) => $insight['is_fixable'])
            ->mapWithKeys(fn ($insight) => [
                $insight['id'] => '['.$insight['id'].'] '.$insight['description'],
            ])
            ->toArray();

        if (empty($fixableInsights)) {
            $this->info('No fixable insights available.');

            return;
        }

        if (confirm('Would you like to fix any of the insights?')) {
            $insightsToFix = multiselect(
                label: 'Which insights would you like to fix?',
                options: $fixableInsights
            );

            foreach ($insightsToFix as $insightId) {
                $this->fixInsight($serverId, $insightId);
            }
        }
    }

    protected function fixInsight(string $serverId, string $insightId): void
    {
        try {
            $this->ploi->fixInsight($serverId, $insightId);
            $this->success("Insight {$insightId} is being processed!");
        } catch (\Exception $e) {
            $this->error("Failed to fix insight {$insightId}: ".$e->getMessage());
        }
    }

    protected function ignoreInsight(string $serverId, string $insightId): void
    {
        try {
            $this->ploi->ignoreInsight($serverId, $insightId);
            $this->success("Insight {$insightId} has been ignored!");
        } catch (\Exception $e) {
            $this->error("Failed to ignore insight {$insightId}: ".$e->getMessage());
        }
    }
}
