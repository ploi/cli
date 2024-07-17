<?php

namespace App\Commands\WebserverTemplate;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\WebserverTemplateService;
use LaravelZero\Framework\Commands\Command;

class WebserverTemplateListCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'webserver-template:list';
    protected $description = 'List all webserver templates in your account';

    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $webserverTemplateService = new WebserverTemplateService();
        $webserverTemplates = $webserverTemplateService->get()->getData();

        $this->table(
            ['ID', 'Label'],
            collect($webserverTemplates)->map(fn($webserverTemplate) => [
                'id'    => $webserverTemplate->id,
                'label' => $webserverTemplate->label,
            ])
        );

    }
}
