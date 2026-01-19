<?php

namespace App\Commands\Site\AuthUser;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAuthUserCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'auth-user:create {--server=} {--site=}';

    protected $description = 'Creates a new authentication user for the site.';

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();

        $name = text(
            label: 'Enter the username:',
            required: true,
            validate: fn (string $value) => match (true) {
                strlen($value) < 2 => 'Username must be at least 2 characters long.',
                strlen($value) > 255 => 'Username cannot exceed 255 characters.',
                ! preg_match('/^[a-zA-Z0-9_-]+$/', $value) => 'Username can only contain letters, numbers, underscores, and hyphens.',
                default => null,
            }
        );

        $password = password(
            label: 'Enter the password:',
            required: true,
            validate: fn (string $value) => match (true) {
                strlen($value) < 2 => 'Password must be at least 2 characters long.',
                strlen($value) > 255 => 'Password cannot exceed 255 characters.',
                default => null,
            }
        );

        $path = text(
            label: 'Enter the protected path (optional):',
            validate: fn (string $value) => match (true) {
                strlen($value) > 0 && ! preg_match('/^\/.*$/', $value) => 'Path must start with a forward slash (/).',
                strlen($value) > 255 => 'Path cannot exceed 255 characters.',
                default => null,
            }
        );

        $data = $this->ploi->createAuthUser($serverId, $siteId, [
            'name' => $name,
            'password' => $password,
            'path' => $path,
        ]);

        if ($data) {
            $this->success('Authentication user created successfully');
        }
    }
}
