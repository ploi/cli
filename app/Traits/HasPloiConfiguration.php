<?php

namespace App\Traits;

trait HasPloiConfiguration
{
    public function hasPloiConfigurationFile(): bool
    {
        return file_exists(getcwd().'/.ploi/settings.yml');
    }

    public function hasPloiProvisionFile(): bool
    {
        return file_exists(getcwd().'/.ploi/provision.yml');
    }
}
