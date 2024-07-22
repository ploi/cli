<?php

namespace App\Commands\Project;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\ProjectService;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\text;

class ProjectCreateCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'project:create {--title? : The title to recognize the project} {--servers? : Server IDs; can\'t\ reuse already attached servers.} {--sites? : Site IDs; can\'t reuse already attached sites.}';
    protected $description = 'Create a project';

    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();


        $title = text(
            label: 'What is the title of the project?',
            required: 'Title is required.',
            hint: 'The title to recognize the project'
        );

        $servers = text(
            label: 'What are the server IDs to attach to the project? Separate by commas',
            hint: 'Server IDs; can\'t reuse already attached servers.'
        );
        $servers = explode(',', $servers);


        $sites = text(
            label: 'What are the site IDs to attach to the project? Separate by commas',
            hint: 'Site IDs; can\'t reuse already attached sites.'
        );
        $sites = explode(',', $sites);

        $this->task("Creating new script", function () use ($title, $servers, $sites) {
            try {
                $projectService = new ProjectService();
                $projectService->create($title, $servers, $sites);
                return true;

            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return false;
            }
        });


    }
}
