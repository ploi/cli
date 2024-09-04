<?php

namespace App\Commands\v1;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Support\Configuration;
use App\Support\Ploi;
use LaravelZero\Framework\Commands\Command;

class DropDatabaseCommand extends Command
{

    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'db:drop';
    protected $description = 'Deletes a given database';

    public function handle(Ploi $ploi, Configuration $configuration)
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $databases = $ploi->getDatabases($configuration->get('server'));
        $sDatabase = $this->menu(
            "Which database you want to delete?",
            collect($databases)->map(fn($database) => $database['name'])->toArray()
        )->open();
        if ($sDatabase === null) exit(1);
        $database = $databases[$sDatabase];

        if ($this->confirm("Are you sure you want to delete database \"{$database['name']}\"", false)) {
            $ploi->deleteDatabase($configuration->get('server'), $database['id']);
            $this->info("✅ Database deleted!");
        } else {
            exit(1);
        }
    }

}
