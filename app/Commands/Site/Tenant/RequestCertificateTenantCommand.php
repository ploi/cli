<?php

namespace App\Commands\Site\Tenant;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class RequestCertificateTenantCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'tenant:request-certificate {--server=} {--site=} {--tenant= : The tenant to request a certificate for} {--webhook} {--force : Bypasses the Ploi DNS validation}';

    protected $description = 'Request an SSL certificate for a tenant';

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
        if (! $tenant) {
            $tenant = select(
                'Select a tenant to request certificate for:',
                $tenants['tenants']
            );
        } elseif (! in_array($tenant, $tenants['tenants'])) {
            $this->error("Tenant '{$tenant}' not found.");

            return;
        }

        $domains = text(
            label: 'Enter domains for the certificate (comma-separated)',
            placeholder: "www.{$tenant}, subdomain.{$tenant}",
            default: $tenant,
            validate: fn (string $value) => match (true) {
                strlen($value) <= 0 => 'Please enter at least one domain.',
                default => $this->validateDomains($value),
            }
        );

        $params = [
            'domains' => str_replace(' ', '', $domains),
            'force' => $this->option('force') ?? false,
        ];

        if ($this->option('webhook')) {
            $webhook = text(
                label: 'Enter webhook URL',
                placeholder: 'https://example.com/webhook',
                validate: fn (string $value) => match (true) {
                    strlen($value) <= 0 => 'Please enter a webhook URL.',
                    ! filter_var($value, FILTER_VALIDATE_URL) => 'Please enter a valid URL.',
                    default => null,
                }
            );

            $params['webhook'] = $webhook;
        }

        try {
            $response = $this->ploi->requestCertificateTenant($serverId, $siteId, $tenant, $params);

            if (isset($response['data'][0]['message']) && str_contains($response['data'][0]['message'], "Let's Encrypt certificate request has been issued")) {
                $this->success("Certificate request initiated for tenant: {$tenant}");
                $this->info('Domains included: '.$params['domains']);
                if (isset($params['webhook'])) {
                    $this->info("Webhook will be triggered at: {$params['webhook']}");
                }
            } else {
                $this->error('Failed to request certificate: '.($response['data'][0]['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error("Error requesting certificate: {$e->getMessage()}");
        }
    }

    public function validateDomains(string $value): ?string
    {
        $domains = array_map('trim', explode(',', $value));

        foreach ($domains as $domain) {
            if (! preg_match('/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/', $domain)) {
                return "Invalid domain format: '$domain'. Please use format like example.com";
            }
        }

        return null;
    }
}
