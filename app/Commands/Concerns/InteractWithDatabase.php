<?php

namespace App\Commands\Concerns;

use function Laravel\Prompts\select;

trait InteractWithDatabase
{
    protected function getDatabase(): int
    {
        $serverId = $this->getServerId();
        $databaseIdentifier = $this->option('database') ?? $this->selectDatabase($serverId);
        $database = $this->getDatabaseDetailsByName($serverId, $databaseIdentifier);

        if (! $database) {
            $this->error('Database must be valid.');
            exit(1);
        }

        return $database['id'];
    }

    protected function selectDatabase($serverId): string
    {
        $databases = collect($this->ploi->databaseList($serverId)['data'])->pluck('name', 'id')->toArray();
        $name = select('Select a database by name:', $databases);

        return $name;
    }

    protected function getDatabaseDetailsByName($serverId, string $name): ?array
    {
        $databases = collect($this->ploi->databaseList($serverId)['data']);

        $database = $databases->first(fn ($database) => $database['name'] === $name);

        if (! $database) {
            $this->error("Database with name '{$name}' not found.");
            exit(1);
        }

        return $database;
    }
}
