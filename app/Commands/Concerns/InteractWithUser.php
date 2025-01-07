<?php

namespace App\Commands\Concerns;

trait InteractWithUser
{
    protected function getUserDetails(): array
    {
        return $this->ploi->checkUser()['data'];
    }
}
