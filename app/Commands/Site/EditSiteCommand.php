<?php

namespace App\Commands\Site;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class EditSiteCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'site:edit {--server=} {--site=} {--domain=}';

    protected $description = 'Edit a website in your server';

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        $domain = $this->option('domain') ?? text(
            label: 'Enter the domain:',
            placeholder: 'E.g. my-site.nl',
            default: $this->site['domain'],
            validate: fn ($value) => preg_match('/^(?!-)([a-zA-Z0-9-]{1,63}\.)+[a-zA-Z]{2,63}$/', $value) ? null : 'Invalid domain format.',
        );

        try {

            $isUpdated = spin(
                function () use ($serverId, $siteId, $domain) {

                    $this->ploi->updateSite($serverId, $siteId, [
                        'root_domain' => $domain,
                    ]);

                    return $this->waitForUpdate($serverId, $siteId, $domain);
                },
                'Updating the site...'
            );

            if ($isUpdated) {
                $this->success("Site {$this->site['domain']} updated successfully to {$domain}. This can take a few moments to fully propagate.");
            } else {
                $this->error('Failed to confirm the site name update. Please check manually.');
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    private function waitForUpdate(string $serverId, string $siteId, string $expectedDomain): bool
    {
        $maxRetries = 10;
        $retryInterval = 5;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            sleep($retryInterval);

            try {
                $siteDetails = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

                if (isset($siteDetails['domain']) && $siteDetails['domain'] === $expectedDomain) {
                    return true;
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        return false;
    }
}
