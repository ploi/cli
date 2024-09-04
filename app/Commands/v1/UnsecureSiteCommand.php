<?php

namespace App\Commands\v1;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Support\Configuration;
use App\Support\Ploi;
use LaravelZero\Framework\Commands\Command;

class UnsecureSiteCommand extends Command
{

    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'ssl:disable';
    protected $description = 'Removes a given certificate';

    public function handle(Ploi $ploi, Configuration $configuration)
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $certificates = $ploi->certificates($configuration->get('server'), $configuration->get('site'));

        $selectedCertificate = $this->menu(
            'Which certificate should get removed?',
            collect($certificates)
                ->map(fn($certificate) => $certificate['domain'])
                ->toArray()
        )->open();

        if ($selectedCertificate === null) exit(1);

        $ploi->unsecure($configuration->get('server'), $configuration->get('site'), $certificates[$selectedCertificate]['id']);

        $this->info("✅ Removing certificate for " . $certificates[$selectedCertificate]['domain'] . "...");
    }
}
