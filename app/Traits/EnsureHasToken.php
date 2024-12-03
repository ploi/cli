<?php

namespace App\Traits;

trait EnsureHasToken
{
    protected function hasToken(): bool
    {
        if (getenv('PLOI_API_TOKEN')) {
            return true;
        }

        return config('ploi.token') !== null && config('ploi.token') !== '';
    }

    protected function ensureHasToken()
    {
        if (! $this->hasToken()) {
            $this->infoLine('Please set your ploi api token first. Call "ploi token <token>" to do that.');
            exit(1);
        }

        return true;
    }
}
