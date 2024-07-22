<?php

namespace App\Commands\StatusPage;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\StatusPageService;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\text;
use function Laravel\Prompts\select;
use function Laravel\Prompts\textarea;

class IncidentCreateCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'incident:create {id? : The id of the status page } {title? : A title for your incident} {description? : A description for your incident} {severity? : The severity for this incident, can be of values: normal, high, maintenance, resolved}';
    protected $description = 'Creates a new incident in your status page';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $id = text(
            label: 'What is the id of the status page?',
            required: 'ID is required.',
        );

        $title = text(
            label: 'What is the title of the incident?',
            required: 'Label is required.',
            hint: 'The title to recognize the incident'
        );
        
        $description = textarea(
            label: 'What is the description of the incident?',
        );

        $severity = select(
            label: 'What is the severity of the incident?',
            options: ['normal', 'high', 'maintenance', 'resolved'],
            default: 'normal',
            hint: 'The severity for this incident (normal, high, maintenance, resolved)',
            required: 'Severity is required.'
        );

        $this->task("Creating new incident", function () use ($id, $title, $description, $severity) {
            try {
                $statusPageService = new StatusPageService();
                $statusPageService->createIncident($id, $title, $description, $severity)->getData();
                return true;

            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return false;
            }
        });


    }
}
