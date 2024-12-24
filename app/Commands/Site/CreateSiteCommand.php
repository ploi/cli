<?php

namespace App\Commands\Site;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;
use Exception;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class CreateSiteCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'create:site {--server=}';

    protected $description = 'Create a site in your server';

    public function handle(): array
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        try {
            $rootDomain = text(
                label: 'What should the domain for your new site be?',
                required: true,
                validate: fn (string $value) => match (true) {
                    strlen($value) <= 0 => 'Domain cannot be empty.',
                    strlen($value) > 100 => 'Domain must be less than 100 characters.',
                    ! preg_match('/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/', $value) => 'Domain must be a valid format (e.g., example.com).',
                    default => null,
                },
                hint: 'Note: Without http(s)://'
            );

            $webDirectory = text(
                label: 'Which directory should be the web directory?',
                default: '/public',
                required: true,
                validate: fn (string $value) => match (true) {
                    strlen($value) <= 0 => 'Web directory cannot be empty.',
                    $value[0] !== '/' => 'Web directory must start with a forward slash (/).',
                    ! preg_match('/^[a-zA-Z0-9\/]*$/', $value) => 'Web directory can only contain letters, numbers, and forward slashes.',
                    strlen($value) >= 50 => 'Web directory must be less than 50 characters.',
                    default => null,
                }
            );

            $systemUser = text(
                label: 'Which system user should run the site?',
                default: 'ploi',
                required: true
            );

            if ($systemUser !== 'ploi') {
                $sysUserSudo = confirm('Should the system user have sudo privileges?', false);

                spin(
                    callback: fn () => $this->ploi->createServerUser($serverId, [
                        'name' => $systemUser,
                        'sudo' => $sysUserSudo,
                    ]),
                    message: 'Creating new system user...'
                );

                $this->success('System user created successfully!');
            }

            $projectType = select('What type of project is this?', [
                '' => 'None (Static HTML or PHP)',
                'laravel' => 'Laravel',
                'nodejs' => 'NodeJS',
                'statamic' => 'Statamic',
                'craft-cms' => 'Craft CMS',
                'symfony' => 'Symfony',
                'wordpress' => 'WordPress',
                'octobercms' => 'OctoberCMS',
                'cakephp' => 'CakePHP 3',
            ]);

            $website = spin(
                callback: fn () => $this->ploi->createSite($serverId, [
                    'root_domain' => $rootDomain,
                    'web_directory' => $webDirectory,
                    'system_user' => $systemUser,
                    'project_type' => $projectType,
                ])['data']['id'],
                message: 'Creating new site...'
            );

            $this->success('Site created successfully!');

            return [
                'id' => $website,
                'domain' => $rootDomain,
            ];
        } catch (Exception $e) {
            $this->error('An error occurred! '.$e->getMessage());
            exit(1);
        }
    }
}
