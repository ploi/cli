<?php

namespace App\Services;

use Ploi\Ploi;

class PloiService
{
    protected Ploi $ploi;

    public function __construct()
    {
        $this->ploi = new Ploi(config('ploi.token'));
    }

}
