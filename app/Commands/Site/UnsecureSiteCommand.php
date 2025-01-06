<?php

namespace App\Commands\Site;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class UnsecureSiteCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'site:unsecure {--server=} {--site=} {--certificate=}';

    protected $description = 'Remove a SSL certificate.';

    protected array $site = [];

    protected string $certId = '';

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];
        $this->certId = '';

        if (! $this->option('certificate')) {
            $this->certId = $this->selectCertificate($serverId, $siteId)['data']['id'];
        }

        if (! confirm(
            'Are you sure you want to remove the SSL certificate?',
            default: true
        )) {
            $this->info('SSL certificate removal cancelled.');

            return;
        }

        $this->ploi->deleteCertificate($serverId, $siteId, $this->certId);
        $this->success('SSL certificate removed.');
    }

    public function selectCertificate($serverId, $siteId): array
    {
        $certs = collect($this->ploi->getCertificates($serverId, $siteId)['data'])->pluck('domain', 'id')->toArray();

        if (! $certs) {
            $this->error('No certificates found for this site.');
            exit(1);
        }

        $certId = select('Select a certificate by domain:', $certs, scroll: 10);

        return ['data' => ['id' => $certId]];
    }
}
