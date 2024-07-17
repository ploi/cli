<?php

namespace App\Commands\StatusPage;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\StatusPageService;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\text;

class IncidentDeleteCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'incident:delete {--id= : The id to delete the incident}';
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
                label: 'What is the id of the incident?',
                required: 'ID is required.',
            );
        }

        $this->task("Deleting incident", function () use ($id) {
            try {
                $statusPageService = new StatusPageService();
                $statusPageService->deleteIncident($id)->getData();
                return true;

            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return false;
            }
        });
    }
}
