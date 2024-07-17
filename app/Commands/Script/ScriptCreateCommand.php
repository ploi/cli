<?php

namespace App\Commands\Script;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\ScriptService;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\text;
use function Laravel\Prompts\select;
use function Laravel\Prompts\textarea;

class ScriptCreateCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'script:create {label? : The label to recognize the script} {user? : The user to run the script as (must be either \'ploi\' or \'root\'} {content? : The content of the script to run}';
    protected $description = 'Create a script';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $label = text(
            label: 'What is the label of the script?',
            required: 'Label is required.',
            hint: 'The label to recognize the script'
        );

        $user = select(
            label: 'What is the user to run the script as?',
            options: ['ploi', 'root'],
            default: 'ploi',
            hint: 'The user to run the script as (must be either \'ploi\' or \'root\')',
            required: 'User is required.'
        );

        $content = textarea(
            label: 'What is the content of the script?',
            required: 'Script is required.',
            hint: 'The content of the script to run'
        );

        $this->task("Creating new script", function () use ($label, $user, $content) {
            try {
                $scriptService = new ScriptService();
                $scriptService->createScript($label, $user, $content)->getData();
                return true;

            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return false;
            }
        });
    }
}
