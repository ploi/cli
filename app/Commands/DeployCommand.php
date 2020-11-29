<?php

namespace App\Commands;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Support\Configuration;
use App\Support\Ploi;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class DeployCommand extends Command
{

    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'deploy:run {--log}';
    protected $description = 'Deploys the current site';

    public function handle(Ploi $ploi, Configuration $configuration)
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $ploi->deploy($configuration->get('server'), $configuration->get('site'));

        $this->info("✅ Deploying...");

        if ($this->option('log')) {
            $this->warn('Waiting for logs...');

            do {
                $logId = $this->getDeploymentLog($ploi, $configuration);
                sleep(1);
            } while ($logId == null);

            $this->watchLog($ploi, $configuration, $logId);
        }
    }

    private function getDeploymentLog(Ploi $ploi, Configuration $configuration): ?string
    {
        $logs = $ploi->getLogs($configuration->get('server'), $configuration->get('site'));
        if (count($logs) == 0) return null;
        $latestLog = $logs[0];

        if (!Str::of($latestLog['created_at_human'])->contains('seconds ago')) {
            return null;
        }

        if ((string)(Str::of($latestLog['created_at_human'])->before('seconds ago')) < 5) {
            return $latestLog['id'];
        }

        return null;
    }

    private function watchLog(Ploi $ploi, Configuration $configuration, string $logId)
    {
        $printed = "";

        while (!str_contains($printed, 'Application deployed!')) {
            $contents = $ploi->getLog($configuration->get('server'), $configuration->get('site'), $logId)['content'];
            $contents = str_replace($printed, "", $contents);
            $this->info($contents);
            $printed .= $contents;
            sleep(1);
        }
    }

}
