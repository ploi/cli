<?php

namespace App\Traits;

use App\Commands\TokenCommand;

trait EnsureHasToken
{
    protected function hasToken(): bool
    {
        if (getenv('PLOI_API_TOKEN')) {
            return true;
        }

        $token = config('ploi.token');

        return ! empty($token);
    }

    protected function ensureHasToken()
    {
        if (! $this->hasToken()) {
            $this->info('Please set your Ploi API token first.');
            $this->call(TokenCommand::class);

            exit(1);
        }

        return true;
    }
}
