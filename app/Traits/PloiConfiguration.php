<?php

namespace App\Traits;

trait PloiConfiguration
{
    public function hasSiteConfiguration(): bool
    {
        return file_exists(getcwd().'/ploi.yml');
    }
}
