<?php

namespace App\Commands\Project;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\ProjectService;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\text;

class ProjectDeleteCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'project:delete {--id= : The ID of the project}';
    protected $description = 'Delete the project for the current user';

    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $projectId = $this->option('id') ?? text(
            label: 'What is the ID of the project?',
            required: 'ID is required.',
        );

        $this->task("Deleting project", function () use ($projectId) {
            try {
                $projectService = new ProjectService();
                $projectService->delete($projectId);
                return true;

            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return false;
            }
        });
    }
}
