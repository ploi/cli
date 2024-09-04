<?php

namespace App\Commands\v1;

use App\Concerns\EnsureHasPloiConfiguration;
use App\Concerns\EnsureHasToken;
use App\Support\Configuration;
use App\Support\Ploi;
use LaravelZero\Framework\Commands\Command;

class ListDatabasesCommand extends Command
{

    use EnsureHasToken;
    use EnsureHasPloiConfiguration;

    protected $signature = 'db:list';
    protected $description = 'Lists all databases on current server';

    public function handle(Ploi $ploi, Configuration $configuration)
    {
        $this->ensureHasToken();
        $this->ensureHasPloiConfiguration();

        $databases = $ploi->getDatabases($configuration->get('server'));

        $this->table([
            'ID',
            'Type',
            'Name',
            'Status'
        ], collect($databases)->map(function ($database) {
            return [
                'id' => $database['id'],
                'type' => $database['type'],
                'name' => $database['name'],
                'status' => $database['status'],
            ];
        }));
    }

}
