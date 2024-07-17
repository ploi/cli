<?php

namespace App\Commands\Script;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\ScriptService;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\text;

class ScriptDeleteCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'script:delete {--id= : The id to delete the script}';
    protected $description = 'Delete a script';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $id = $this->option('id');

        if(!$id){
            $id = text(
                label: 'What is the id of the script?',
                required: 'ID is required.',
            );
        }

        $this->task("Deleting script", function () use ($id) {
            try {
                $scriptService = new ScriptService();
                $scriptService->deleteScript($id)->getData();
                return true;

            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return false;
            }
        });
    }
}
