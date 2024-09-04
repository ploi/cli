<?php

namespace App\Commands\v1;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Support\Configuration;
use App\Support\Ploi;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class CreateDatabaseCommand extends Command
{

    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'db:create {name?} {--user=} {--G|generate-password}';
    protected $description = 'Creates a database on current server';

    public function handle(Ploi $ploi, Configuration $configuration)
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $name = $this->argument('name') ?? $this->ask("What should be the name of the database?");
        $user = $this->option('user');
        if ($this->option('generate-password')) {
            if (!$user) $user = $name;
            $password = Str::random();
        } else if ($user) {
            $password = $this->secret("Please enter the password for the database");
        }

        $ploi->createDatabase(
            $configuration->get('server'),
            $name,
            $user,
            $password ?? null
        );

        $this->line("✅ Created database {$name}!");
        if ($this->option('generate-password')) {
            $this->warn("Generated password for user \"{$user}\": {$password}");
        }
    }

}
