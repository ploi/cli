<?php

namespace App\Commands\v1;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Support\Configuration;
use App\Support\Ploi;
use LaravelZero\Framework\Commands\Command;

class OpenCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'open {--test}';

    protected $description = 'Open the site in a browser window.';

    public function handle(Ploi $ploi, Configuration $configuration)
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $data = $ploi->getTestDomain($configuration->get('server'), $configuration->get('site'));

        $domain = $data['domain'];

        if ($this->option('test')) {
            $domain = $data['test_domain'];

            if (is_null($domain)) {
                $this->error('No test domain exists!');
                return 1;
            }
        }

        $this->info(sprintf('Opening https://%s ..', $domain));

        passthru($this->buildCommand($domain));
    }

    protected function buildCommand(string $domain): string
    {
        $executables = [
            'windows' => 'start',
            'linux' => 'xdg-open',
            'osx' => 'open',
        ];

        $executable = $executables[strtolower(PHP_OS_FAMILY)] ?? null;

        if (is_null($executable)) {
            $this->error(sprintf('Sorry %s isn\'t supported', PHP_OS_FAMILY));
            exit(1);
        }

        return sprintf('%s https://%s', $executable, $domain);
    }
}
