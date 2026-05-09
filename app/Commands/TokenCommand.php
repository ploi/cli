<?php

namespace App\Commands;

use App\Traits\EnsureHasToken;

use function Laravel\Prompts\text;

class TokenCommand extends Command
{
    use EnsureHasToken;

    protected $signature = 'token {--token= : The Ploi.io API token.} {--force : Force override the token.}';

    protected $description = 'Connect to your Ploi.io account.';

    public function handle(): void
    {

        if ($this->hasToken() && ! $this->option('force')) {
            $this->warn('You already have set a token! Use "--force" to override it.');

            return;
        }

        $this->info('Welcome to Ploi CLI!');
        $this->info('Please enter your Ploi API token to get started. Create one here: https://ploi.io/profile/api-keys');

        if ($token = $this->option('token')) {
            $this->saveToken($token);
            $this->checkToken();

            return;
        }

        $token = text(
            label: 'Please Enter Your Ploi Access Token:',
            required: true,
            hint: 'Visit the docs on: https://cli.ploi.io'
        );

        $this->saveToken($token);

        $this->checkToken();

    }

    protected function saveToken($token): void
    {
        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.ploi',
            'config.php',
        ]);

        $config = [
            'token' => $token,
        ];

        if (! file_exists(dirname($configFile))) {
            @mkdir(dirname($configFile), 0775, true);
        }

        file_put_contents($configFile, '<?php return '.var_export($config, true).';');

        config(['ploi.token' => $token]);

        $this->info('Token saved successfully.');
    }

    private function checkToken(): void
    {
        $token = config('ploi.token');

        $this->ploi->setToken($token);

        $response = $this->ploi->checkUser();

        if (! $response) {
            $this->error('Invalid token. Please try again.');

            return;
        }

        $user = $response['data'];
        $this->success('Connected as: '.$user['name'].' ('.$user['email'].')');
    }
}
