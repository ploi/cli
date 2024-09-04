<?php

namespace App\Commands\v1;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Support\Configuration;
use App\Support\Ploi;
use LaravelZero\Framework\Commands\Command;

class ListCertificatesCommand extends Command
{

    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'ssl:list';
    protected $description = 'Prints all ssl certificates';

    public function handle(Ploi $ploi, Configuration $configuration)
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $certificates = $ploi->certificates($configuration->get('server'), $configuration->get('site'));

        $this->table([
            'ID',
            'Status',
            'Domain(s)',
            'Expires at'
        ], collect($certificates)->map(fn($certificate) => [
            'id' => $certificate['id'],
            'status' => $certificate['status'],
            'domain' => $certificate['domain'],
            'expires_at' => $certificate['expires_at'],
        ]));
    }
}
