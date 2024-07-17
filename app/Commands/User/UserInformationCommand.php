<?php

namespace App\Commands\User;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Services\UserService;
use LaravelZero\Framework\Commands\Command;
use function Termwind\render;

class UserInformationCommand extends Command
{
    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'user:information';
    protected $description = 'Get your own user information';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $userService = new UserService();
        $user = $userService->information()->getData();

        render(
            view('user.user-information', ['user' => $user])
        );
    }
}
