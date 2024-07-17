<?php

namespace App\Commands\Script;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\ScriptService;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\text;

class ScriptGetCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'script:get {--id= : The id to get the script}';
    protected $description = 'Get a script from your account';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $scriptId = $this->option('id');

        if(!$scriptId){
            $scriptId = text(
                label: 'What is the id of the script?',
                required: 'ID is required.',
            );
        }

        $scriptService = new ScriptService();
        $script = $scriptService->getScripts($scriptId)->getData();

        $this->line($script->content);
    }
}
