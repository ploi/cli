<?php

namespace App\Commands\StatusPage;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\StatusPageService;
use LaravelZero\Framework\Commands\Command;

class StatusPageListCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'status-page:list';
    protected $description = 'List all your status pages';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $statusPageService = new StatusPageService();
        $scripts = $statusPageService->getStatusPages()->getData();

        $this->table(
            ['ID', 'Name', 'Slug', 'Description', 'Incidents'],
            collect($scripts)->map(fn($statuspage) => [
                'id'           => $statuspage->id,
                'name'         => $statuspage->name,
                'slug'         => $statuspage->slug,
                'description'  => $statuspage->description ?? 'N/A',
                'no_incidents' => $statuspage->theme->no_incidents ? 'No' : 'Yes',
            ])
        );
    }
}
