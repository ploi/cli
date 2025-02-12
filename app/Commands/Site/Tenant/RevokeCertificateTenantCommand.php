<?php

namespace App\Commands\Site\Tenant;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class RevokeCertificateTenantCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'tenant:revoke-certificate {--server=} {--site=} {--tenant= : The tenant to revoke the certificate for} {--webhook}';

    protected $description = 'Revoke an SSL certificate for a tenant';

    protected array $site;
    protected array $server;

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];
        $this->server = $this->ploi->getServerDetails($serverId)['data'];

        $tenants = $this->ploi->getTenants($serverId, $siteId)['data'];

        if (empty($tenants['tenants'])) {
            $this->warn("No tenants found for site {$tenants['main']}.");
            return;
        }

        $tenant = $this->option('tenant');
        if (!$tenant) {
            $tenant = select(
                'Select a tenant to revoke certificate for:',
                $tenants['tenants']
            );
        } elseif (!in_array($tenant, $tenants['tenants'])) {
            $this->error("Tenant '{$tenant}' not found.");
            return;
        }

        $params = [];
        if ($this->option('webhook')) {
            $webhook = text(
                label: 'Enter webhook URL',
                placeholder: 'https://example.com/webhook',
                validate: fn (string $value) => match (true) {
                    strlen($value) <= 0 => 'Please enter a webhook URL.',
                    !filter_var($value, FILTER_VALIDATE_URL) => 'Please enter a valid URL.',
                    default => null,
                }
            );

            $params['webhook'] = $webhook;
        }

        try {
            $response = $this->ploi->revokeCertificateTenant($serverId, $siteId, $tenant, $params);

            if (isset($response['data'][0]['message']) && str_contains($response['data'][0]['message'], "certificate has been revoked")) {
                $this->success("Certificate successfully revoked for tenant: {$tenant}");
                if (isset($params['webhook'])) {
                    $this->info("Webhook will be triggered at: {$params['webhook']}");
                }
            } else {
                $this->error("Failed to revoke certificate: " . ($response['data'][0]['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error("Error revoking certificate: {$e->getMessage()}");
        }
    }
}
