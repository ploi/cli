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
            $this->warnLine('You already have set a token! Use "--force" to override it.');

            return;
        }

        $this->infoLine('Welcome to Ploi.io CLI!');
        $this->infoLine('Please enter your Ploi.io API token to get started.');

        if ($token = $this->option('token')) {
            $this->saveToken($token);
            $this->checkToken();

            return;
        }

        $token = text(
            label: 'Please Enter Your Ploi.io Access Token:',
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

        $this->infoLine('Token saved successfully.');
    }

    private function checkToken(): void
    {
        $token = config('ploi.token');

        $this->ploi->setToken($token);

        $response = $this->ploi->checkUser();

        if (! $response) {
            $this->errorLine('Invalid token. Please try again.');

            return;
        }

        $user = $response['data'];
        $this->successLine('Connected as: '.$user['name'].' ('.$user['email'].')');
    }
}
