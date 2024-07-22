<?php

namespace App\Commands\Project;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\ProjectService;
use LaravelZero\Framework\Commands\Command;

class ProjectListCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'project:list';
    protected $description = 'List all the projects for the current user';

    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $projectService = new ProjectService();
        $projects = $projectService->list()->getData();

        $this->table(
            ['ID', 'Title', 'Servers', 'Sites', 'Created At'],
            collect($projects)->map(fn($project) => [
                'id'         => $project->id,
                'title'      => $project->title,
                'servers'    => collect($project->servers)->pluck('name')->implode(', '),
                'sites'      => collect($project->sites)->pluck('domain')->implode(', '),
                'created_at' => $project->created_at,
            ])
        );

    }
}
