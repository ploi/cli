<?php

namespace App\Commands\v1;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Support\Configuration;
use App\Support\Ploi;
use LaravelZero\Framework\Commands\Command;

class PushEnvCommand extends Command
{

    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'env:push {--filename=}';
    protected $description = 'Uploads the current environment file to ploi.';

    public function handle(Ploi $ploi, Configuration $configuration)
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $filename = $this->option('filename') ?? '.env.ploi';

        if (!file_exists($filename)) {
            $this->error("File {$filename} not found!");
            exit(1);
        }

        $env = file_get_contents($filename);

        if ($ploi->pushEnvironmentFile($configuration->get('server'), $configuration->get('site'), $env)) {
            $this->info("✅ Uploaded {$filename} to ploi!");

            if (!$this->confirm("Do you want to keep the local {$filename} file?", false)) {
                unlink($filename);
            }
        } else {
            $this->error("Error while uploading!");
        }
    }

}
