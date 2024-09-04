<?php

namespace App\Commands\v1;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Support\Configuration;
use App\Support\Ploi;
use LaravelZero\Framework\Commands\Command;

class PushDeployScriptCommand extends Command
{

    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'deploy:push {--filename=}';
    protected $description = 'Uploads the current deploy script file to ploi.';

    public function handle(Ploi $ploi, Configuration $configuration)
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $filename = $this->option('filename') ?? 'deploy.sh';

        if (!file_exists($filename)) {
            $this->error("File {$filename} not found!");
            exit(1);
        }

        $contents = file_get_contents($filename);

        if ($ploi->pushDeployScript($configuration->get('server'), $configuration->get('site'), $contents)) {
            $this->info("✅ Uploaded {$filename} to ploi!");
        } else {
            $this->error("Error while uploading!");
        }

        if (!$this->confirm("Do you want to keep the local {$filename} file?", false)) {
            unlink($filename);
        }
    }

}
