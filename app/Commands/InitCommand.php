<?php

namespace App\Commands;

use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;
use Exception;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class InitCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'init {--force : Force reinitialization of the configuration}';

    protected $description = 'Initialize your site with Ploi.io';

    protected array $server = [];

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        if ($this->isAlreadyInitialized()) {
            $this->error('This site is already initialized! Use --force to reinitialize.');
            exit(1);
        }

        $server = $this->selectServer();
        $serverId = $this->getServerIdByNameOrIp($server);

        $createNewSite = confirm("Do you want to create a new site on server <fg=green>[{$server}]</>?", false);

        $siteDetails = $createNewSite
            ? $this->createNewSite($serverId)
            : $this->selectExistingSite($serverId);

        $this->configuration->initialize($serverId, $siteDetails['id'], getcwd(), $siteDetails['domain']);

        $this->linkProject($createNewSite, $siteDetails['domain']);

//        $installRepo = confirm('Do you want to initialize the repository?');
//        if ($installRepo) {
//            $this->call('install:repo', ['--server' => $serverId, '--site' => $siteDetails['id']]);
//        }
    }

    protected function isAlreadyInitialized(): bool
    {
        return $this->hasPloiConfigurationFile() && ! $this->option('force');
    }

    protected function createNewSite($serverId): array
    {
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

    protected function linkProject(bool $createNewSite, string $domain): void
    {
        if (! $createNewSite) {
            $this->success("Your project is linked to {$domain}, the settings file has been created in the .ploi folder.");
        }
    }

    protected function selectExistingSite(int $serverId): array
    {
        $sites = collect($this->ploi->getSiteList($serverId)['data'])->pluck('domain', 'id')->toArray();
        $siteData = select('On which site you want to install the repository?', $sites);

        return ['id' => $siteData, 'domain' => $sites[$siteData]];
    }
}
