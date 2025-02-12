<?php

namespace App\Commands\Site\Repository;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;
use App\Traits\HasRepo;
use Exception;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class InstallRepoCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, HasRepo, InteractWithServer, InteractWithSite;

    protected $signature = 'repository:install {--server=} {--site=}';

    protected $description = 'Install the repository to your site';

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $siteDetails = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        if (! $this->hasRepoInstalled($serverId, $siteDetails['id'])) {
            $this->initializeRepository($serverId, $siteDetails, $siteDetails['domain']);
        } else {
            $this->warn('This site already has a repository installed!');
        }
    }

    protected function initializeRepository($serverId, $siteDetails, $domain): void
    {
        $localGitRepo = $this->getLocalGitRepositoryUrl();
        if (Str::startsWith($localGitRepo, 'fatal')) {
            $this->error('This site is not a git repository!');
            exit(1);
        }

        $provider = $this->getProvider();
        $repoUrl = $this->confirmRepoUrl($localGitRepo);
        $branch = text('Which branch should be installed?', 'main');

        $repo = $this->ploi->installRepository($serverId, $siteDetails['id'], [
            'provider' => $provider,
            'branch' => $branch,
            'name' => $repoUrl,
        ])['data'];

        if (! $repo) {
            $this->error('An error occurred while installing the repository! Please check your repository, provider, and permissions and try again.');
            exit(1);
        }

        $domain = $siteDetails['test_domain'] ?: $domain;

        $this->info('Repository initialized! Go do some great stuff 🚀');
        $this->success("You can see your project at: {$domain}");
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
            hint: "Must be either: 'username/repository' or a custom GIT URL ending with .git"
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
