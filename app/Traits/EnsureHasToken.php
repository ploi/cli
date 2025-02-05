<?php

namespace App\Traits;

trait EnsureHasToken
{
    protected function hasToken(): bool
    {
        if (getenv('PLOI_API_TOKEN')) {
            return true;
        }

        $token = config('ploi.token');
        return !empty($token);
    }

    protected function ensureHasToken()
    {
        if (!$this->hasToken()) {
            $this->info('Please set your ploi api token first.');
            $this->call('ploi:token');
            exit(1);
        }

        return true;
    }

}
