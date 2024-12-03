<?php

namespace App\Commands;

//use App\Services\PloiConfig as PloiConfig;
use App\Services\PloiAPI;
use App\Support\Configuration;
use LaravelZero\Framework\Commands\Command as BaseCommand;

abstract class Command extends BaseCommand
{
    protected PloiAPI $ploi;

    protected Configuration $configuration;

    public function __construct()
    {
        parent::__construct();

        $this->ploi = new PloiAPI;
        $this->configuration = new Configuration;
    }

    public function infoLine($message): void
    {
        $this->info("<fg=blue>==></><options=bold>{$message}</>");
    }

    public function errorLine($message): void
    {
        $this->error("<fg=red>==></><options=bold>{$message}</>");
    }

    public function successLine($message): void
    {
        $this->info("<fg=green>==></><options=bold>{$message}</>");
    }

    public function warnLine($message): void
    {
        $this->warn("<fg=yellow>==></><options=bold>{$message}</>");
    }
}
