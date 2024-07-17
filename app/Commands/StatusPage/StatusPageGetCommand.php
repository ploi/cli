<?php

namespace App\Commands\StatusPage;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\StatusPageService;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\text;

class StatusPageGetCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'status-page:get {--id= : The id to get the status page}';
    protected $description = 'Get the selected status page';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $statuspageId = $this->option('id');

        if (!$statuspageId) {
            $statuspageId = text(
                label: 'What is the id of the script?',
                required: 'ID is required.',
            );
        }

        $statusPageService = new StatusPageService();
        $script = $statusPageService->getStatusPages($statuspageId)->getData();

        $this->table(
            [
                'ID',
                'Name',
                'Slug',
                'Description',
                'Incidents'
            ],
            [
                [
                    'id'          => $script->id,
                    'name'        => $script->name,
                    'slug'        => $script->slug,
                    'description' => $script->description ?? 'N/A',
                    'incidents'   => $script->theme->no_incidents ? 'No' : 'Yes',
                ],
            ]
        );

        $this->table(
            [
                'Logo',
                'Primary',
                'Secondary',
                'Branding',
                'lightMode'
            ],
            [
                [
                    'logo'      => $script->theme->logo ?? 'N/A',
                    'primary'   => $script->theme->primary ?? 'N/A',
                    'secondary' => $script->theme->secondary ?? 'N/A',
                    'branding'  => $script->theme->branding ? 'Yes' : 'No',
                    'lightMode' => $script->theme->lightMode ? 'Light' : 'Dark',
                ],
            ]
        );
    }
}
