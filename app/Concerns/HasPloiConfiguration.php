<?php


namespace App\Concerns;


trait HasPloiConfiguration
{

    public function hasPloiConfiguration(): bool
    {
        return file_exists(getcwd() . '/ploi.yml');
    }

}
