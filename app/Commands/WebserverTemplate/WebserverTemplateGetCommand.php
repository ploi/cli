<?php

namespace App\Commands\WebserverTemplate;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\WebserverTemplateService;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\text;

class WebserverTemplateGetCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'webserver-template:get {--id= : The id to get the webserver template}';
    protected $description = 'Show a webserver template from your account';

    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $webserverTemplateId = $this->option('id');

        if(!$webserverTemplateId){
            $webserverTemplateId = text(
                label: 'What is the id of the webserver template?',
                required: 'ID is required.',
            );
        }

        $webserverTemplateService = new WebserverTemplateService();
        $webserverTemplate = $webserverTemplateService->get($webserverTemplateId)->getData();

        $this->info('Webserver Template: ' . $webserverTemplate->label);

        $this->line($webserverTemplate->content);

    }
}
