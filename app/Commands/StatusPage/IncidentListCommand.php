<?php

namespace App\Commands\StatusPage;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\StatusPageService;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\text;

class IncidentListCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'incident:list {--id= : The id of the status page}';
    protected $description = 'Get all the incidents for a status page ordered by latest entry';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $statusPageId = $this->option('id');

        if (!$statusPageId) {
            $statusPageId = text(
                label: 'What is the id of the status page?',
                required: 'ID is required.',
            );
        }

        $statusPageService = new StatusPageService();
        $incidents = $statusPageService->getIncidents($statusPageId)->getData();

        $this->table(
            ['ID', 'Title', 'Description', 'Severity'],
            collect($incidents)->map(fn($incident) => [
                'id'      => $incident->id,
                'title'    => $incident->title,
                'description'  => $incident->description,
                'severity' => $incident->severity,
            ])
        );

    }
}
