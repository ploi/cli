<?php

namespace App\Commands\Site\SSL;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class SecureSiteCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'site:secure {--server=} {--site=} {--force} {--custom} {--domains=*} {--private=}';

    protected $description = 'Secure a site with SSL certificates.';

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        $params = [
            'type' => $this->option('custom') ? 'custom' : 'letsencrypt',
        ];

        if ($this->option('custom')) {
            if (! $this->option('domains') || ! $this->option('private')) {
                $params['certificate'] = text(
                    label: 'Enter the domain(s) you want to secure (comma separated):'
                );

                $params['private'] = text(
                    label: 'Enter the private key:'
                );

                return;
            }

            $params['certificate'] = $this->option('domains');
            $params['private'] = $this->option('private');
        } else {
            if (empty($this->option('domains'))) {
                $params['certificate'] = text(
                    label: 'Enter the domain(s) you want to secure (comma separated):'
                );
            } else {
                $params['certificate'] = implode(',', $this->option('domains'));
            }
        }

        if ($this->option('force')) {
            $params['force'] = true;
        }

        $cert = $this->ploi->createCertificate($serverId, $siteId, $params)['data'];

        $statusCheck = spin(
            callback: function () use ($serverId, $siteId, $cert) {
                while (true) {
                    sleep(5);

                    $CertStatus = $this->ploi->getCertificateDetails($serverId, $siteId, $cert['id'])['data']['status'] ?? 'created';

                    $statusMap = [
                        'active' => ['type' => 'success', 'message' => "SSL certificates have been installed successfully for {$cert['domain']}."],
                        'failed' => ['type' => 'error', 'message' => 'SSL certificates installation failed. Please check manually.'],
                    ];

                    return $statusMap[$CertStatus] ?? ['type' => 'warn', 'message' => 'SSL certificates installation failed. Please check manually.'];
                }
            },
            message: 'Checking certificate status...'
        );

        $this->console($statusCheck['message'], $statusCheck['type']);
    }
}
