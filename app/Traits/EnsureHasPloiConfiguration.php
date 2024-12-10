<?php

namespace App\Traits;

trait EnsureHasPloiConfiguration
{
    public function ensureHasPloiConfiguration()
    {
        if (! $this->hasPloiConfiguration()) {
            $this->error('You have not yet linked this project to Ploi. Please run `ploi init` first.');
            exit(1);
        }

        return true;
    }

    public function hasPloiConfiguration(): bool
    {
        return file_exists(getcwd().'/ploi.yml');
    }
}
