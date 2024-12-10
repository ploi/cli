<?php

namespace App\Commands\Site;

use App\Commands\Command as BaseCommand;
use App\Traits\EnsureHasPloiConfiguration;
use App\Traits\EnsureHasToken;
use App\Traits\HasRepo;
use Exception;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class InstallRepo extends BaseCommand
{
    use EnsureHasPloiConfiguration, EnsureHasToken, HasRepo;

    protected $signature = 'install:repo {--server=} {--site=}';

    protected $description = 'Install the repository to your site';

    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $serverId = $this->option('server');
        $siteId = $this->option('site');

        if (! $serverId || ! $siteId) {
            $serverId = $this->selectServer();
            $siteDetails = $this->selectSite($serverId);
        } else {
            $siteDetails = $this->ploi->getSiteDetails($serverId, $siteId);
        }

        if (! $this->hasRepoInstalled($serverId, $siteDetails['id'])) {
            $this->initializeRepository($serverId, $siteDetails, $siteDetails['domain']);
        } else {
            $this->warn('This site already has a repository installed!');
        }

    }

    protected function selectServer(): int|string
    {
        if ($this->ploi->getServerList()['data'] === null) {
            $this->error('No servers found! Please create a server first.');
            exit(1);
        }

        $servers = collect($this->ploi->getServerList()['data'])->pluck('name', 'id')->toArray();

        return select('Select a server:', $servers);
    }

    protected function selectSite($serverId): array
    {
        $sites = collect($this->ploi->getSiteList($serverId)['data'])->pluck('domain', 'id')->toArray();
        $siteId = select('On which site you want to install the repository?', $sites);

        return ['id' => $siteId, 'domain' => $sites[$siteId]];
    }

    protected function initializeRepository($server, $site, $domain): void
    {
        $localGitRepo = $this->getLocalGitRepositoryUrl();
        if (Str::startsWith($localGitRepo, 'fatal')) {
            $this->error('This site is not a git repository!');
            exit(1);
        }

        $provider = $this->getProvider();
        $repoUrl = $this->confirmRepoUrl($localGitRepo);
        $branch = text('Which branch should be installed?', 'main');

        if (! $this->ploi->installRepository($server['id'], $site['id'], [
            'provider' => $provider,
            'branch' => $branch,
            'name' => $repoUrl,
        ])) {
            $this->error('An error occurred while installing the repository! Please check your repository, provider and permissions and try again.');
            exit(1);
        }

        $testDomain = $this->ploi->enableTestDomain($server['id'], $site['id'])['full_test_domain'] ?? $domain;
        $this->info('Repository initialized! Go do some great stuff 🚀');
        $this->warn("You can see your project at: {$testDomain}");
    }

    protected function getProvider(): string
    {
        $this->warn('Make sure that you have connected the provider to your Ploi.io account!');

        return select('Which provider do you use?', ['github', 'gitlab', 'bitbucket', 'custom']);
    }

    protected function confirmRepoUrl(string $repo): string
    {
        $repo = Str::of($repo)->replace('.git', '')->after(':');

        return text(
            label: 'Please confirm your repository URL',
            default: $repo,
            hint: "Must be either: 'username/repository' or a custom GIT url ending with .git"
        );
    }

    protected function getLocalGitRepositoryUrl(): string
    {
        try {
            $output = null;
            $returnVar = null;

            exec('git remote get-url origin 2>&1', $output, $returnVar);

            if ($returnVar !== 0) {
                $this->warn('This directory is not a git repository!');

                return false;
            }

            return implode("\n", $output);
        } catch (Exception $e) {
            $this->error('An unexpected error occurred: '.$e->getMessage());

            return false;
        }
    }
}
