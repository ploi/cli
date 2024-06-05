<?php

namespace App\Commands;

use App\Concerns\EnsureHasToken;
use App\Support\Ploi;
use App\Support\TokenNodeVisitor;
use Exception;
use LaravelZero\Framework\Commands\Command;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php8;
use PhpParser\PrettyPrinter\Standard;
use function Laravel\Prompts\text;


class LoginCommand extends Command
{

    use EnsureHasToken;


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'login {--token= : The Ploi.io token to use.} {--force : Force to override the token.}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store your Ploi.io api token.';


    protected String $user = '';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if ($this->hasToken() && !$this->option('force')) {
            $this->warn('You already have set a token! Use "ploi token --force" to override your token.');
            return;
        }

        $token = $this->option('token');

        if (!$token) {
            $token = text(
                label: 'Please Enter Your Ploi.io Access Token:',
                required: true,
                hint: 'You can create your token in your Ploi.io account settings. Visit: https://ploi.io/profile/api-keys'
            );
        }

        $this->line('<fg=blue>==></> Checking token...');

        if ($this->checkToken($token)) {
            $this->info('==> Token valid.');
            $this->info('==> Token saved!');
            $this->info('==> Authenticated as <fg=green>' . $this->user . '</>');
            $this->saveToken($token);
        } else {
            $this->error('==> Cannot login with given token!');
        }
    }

    protected function checkToken(string $token): bool
    {
        $ploi = new Ploi($token);

        try {
            $user = $ploi->getUser();
            $this->user = $user['email'];
            return $user['email'];
        } catch (Exception $e) {
            return false;
        }
    }

    protected function modifyConfigurationFile(string $configFile, string $token): string
    {
        $lexer = new Emulative();
        $parser = new Php8($lexer);

        $oldStmts = $parser->parse(file_get_contents($configFile));
        $oldTokens = $lexer->tokenize(file_get_contents($configFile));

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new CloningVisitor());
        $newStmts = $nodeTraverser->traverse($oldStmts);

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new TokenNodeVisitor($token));

        $newStmts = $nodeTraverser->traverse($newStmts);

        $prettyPrinter = new Standard();

        return $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
    }

    protected function saveToken($token): void
    {
        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.ploi',
            'config.php',
        ]);

        if (!file_exists($configFile)) {
            @mkdir(dirname($configFile), 0775, true);
            $updatedConfigFile = $this->modifyConfigurationFile(base_path('config/ploi.php'), $token);
        } else {
            $updatedConfigFile = $this->modifyConfigurationFile($configFile, $token);
        }

        file_put_contents($configFile, $updatedConfigFile);

        return;
    }

}
