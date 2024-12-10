<?php

namespace App\Commands;

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

    public function console($string, $type, $verbosity = null): void
    {
        $this->$type($string, $verbosity);
    }

    public function info($string, $verbosity = null): void
    {
        parent::info("<fg=blue>==></><options=bold> {$string}</>", $verbosity);
    }

    public function error($string, $verbosity = null): void
    {
        parent::error("<fg=red>==></><options=bold> {$string}</>", $verbosity);
    }

    public function success($string, $verbosity = null): void
    {
        parent::info("<fg=green>==></><options=bold> {$string}</>", $verbosity);
    }

    public function warn($string, $verbosity = null): void
    {
        parent::warn("<fg=yellow>==></><options=bold> {$string}</>", $verbosity);
    }
}
