<?php

namespace App\Commands\Project;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\ProjectService;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\text;

class ProjectGetCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'project:get {--id= : The id to get the project}';
    protected $description = 'Get a project from your account';

    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $projectId = $this->option('id');
        if (!$projectId) {
            $projectId = text(
                label: 'What is the id of the project?',
                required: 'ID is required.',
            );
        }

        $projectService = new ProjectService();
        $project = $projectService->get($projectId)->getData();

        $this->table(
            ['ID', 'Title', 'Servers', 'Sites', 'Created At'],
            [
                [
                    'id'         => $project->id,
                    'title'      => $project->title,
                    'servers'    => collect($project->servers)->pluck('name')->implode(', '),
                    'sites'      => collect($project->sites)->pluck('domain')->implode(', '),
                    'created_at' => $project->created_at,
                ]
            ]
        );

    }
}
