<?php

namespace App\Commands\Script;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\ScriptService;
use LaravelZero\Framework\Commands\Command;

class ScriptListCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'script:list';
    protected $description = 'List all the scripts for the current user';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $scriptsService = new ScriptService();
        $scripts = $scriptsService->getScripts()->getData();

        $this->table(
            ['ID', 'Label', 'User', 'Created At'],
            collect($scripts)->map(fn($script) => [
                'id'         => $script->id,
                'label'      => $script->label,
                'user'       => $script->user,
                'created_at' => $script->created_at,
            ])
        );
    }
}
