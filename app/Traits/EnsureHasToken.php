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

        if (!$this->validateToken()) {
            $this->info('Your ploi api token is invalid. Please set a valid token.');
            $this->call('ploi:token');
            exit(1);
        }

        return true;
    }

    protected function validateToken(): bool
    {
        $response = $this->ploi->checkUser();

        if (empty($response) || !is_array($response)) {
            return false;
        }

        $data = $response['data'] ?? [];

        if (empty($data) || !is_array($data)) {
            return false;
        }

        if (isset($data[0]['message']) && $data[0]['message'] === 'Unauthenticated.') {
            return false;
        }

        return true;
    }
}
