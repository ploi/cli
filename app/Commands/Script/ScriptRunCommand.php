<?php

namespace App\Commands\Script;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\ScriptService;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\text;

class ScriptRunCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'script:run {--id= : The id to run the script} {--server= : The server id(s) to run the script on}';
    protected $description = 'Run a script for specific server(s)';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $scriptId = $this->option('id');
        $serverIds = $this->option('server');

        if(!$scriptId){
            $scriptId = text(
                label: 'What is the id of the script?',
                required: 'ID is required.',
            );
        }

        if(!$serverIds){
            $serverIds = text(
                label: 'What is the server id(s) to run the script on?',
                required: 'Server ID is required.',
                hint: 'Separate multiple server IDs with a comma (,) for example: 1,2,3'
            );
        }

        $serverIds = explode(',', $serverIds);

        $this->task("Running script on servers", function () use ($scriptId, $serverIds) {
            try {
                $scriptService = new ScriptService();
                $scriptService->runScript($scriptId, $serverIds)->getData();
                return true;

            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return false;
            }
        });
    }
}
