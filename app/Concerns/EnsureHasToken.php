<?php

namespace App\Concerns;

trait EnsureHasToken
{

    protected function hasToken(): bool
    {
        return config('ploi.token') !== null && config('ploi.token') !== '';
    }

    protected function ensureHasToken()
    {
        if (!$this->hasToken()) {
            $this->error('Please set your ploi api token first. Call "ploi token <token>" to do that.');
            exit(1);
        }

        return true;
    }

}
