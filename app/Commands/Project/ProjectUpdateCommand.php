<?php

namespace App\Commands\Project;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\ProjectService;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\text;

class ProjectUpdateCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'project:update {--id= : The ID of the project} {--title= : The title to recognize the project} {--servers= : Server IDs; can\'t\ reuse already attached servers.} {--sites= : Site IDs; can\'t reuse already attached sites.}';
    protected $description = 'Update a project';

    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $id = $this->option('id') ?? text(
            label: 'What is the ID of the project?',
            required: 'ID is required.',
        );

        $projectService = new ProjectService();
        $project = $projectService->get($id)->getData();

        $title = text(
            label: 'What is the title of the project?',
            default: $project->title,
            required: 'Title is required.',
            hint: 'The title to recognize the project'
        );

        $servers = text(
            label: 'What are the server IDs to attach to the project? Separate by commas',
            default: implode(',', collect($project->servers)->pluck('id')->toArray()),
            hint: 'Server IDs; can\'t reuse already attached servers.'
        );
        $servers = explode(',', $servers);


        $sites = text(
            label: 'What are the site IDs to attach to the project? Separate by commas',
            default: implode(',', collect($project->sites)->pluck('id')->toArray()),
            hint: 'Site IDs; can\'t reuse already attached sites.'
        );
        $sites = explode(',', $sites);

        $this->task("Updating script", function () use ($id, $title, $servers, $sites) {
            try {
                $projectService = new ProjectService();
                $projectService->update($id, $title, $servers, $sites);
                return true;

            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return false;
            }
        });


    }
}
