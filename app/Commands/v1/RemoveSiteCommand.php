<?php

namespace App\Commands\v1;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Support\Configuration;
use App\Support\Ploi;
use LaravelZero\Framework\Commands\Command;

class RemoveSiteCommand extends Command
{

    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'remove';
    protected $description = 'Deletes site or un-initializes it.';

    public function handle(Ploi $ploi, Configuration $configuration)
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        if (!$this->confirm('Are you sure to delete this site?')) exit(1);

        if ($this->confirm('Do you also want to remove the remote site on ploi?')) {
            $this->warn('Deleting remote site...');
            $ploi->deleteSite($configuration->get('server'), $configuration->get('site'));
            $this->info("✅ Remote site deleted!");
        }

        $this->info('Removing ploi-cli files...');

        unlink('ploi.yml');

        $this->info("✅ Removed ploi.yml file.");
        $this->line("");
        $this->warn("Thank you very much for using Ploi-CLI. I'm sad to see you going. Please let me know any feedback on hallo@felix-schmid.de");
    }
}
