<?php

namespace App\Commands\Site\Alias;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class DeleteAliasCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'alias:delete {--server=} {--site=} {--alias= : The name of the alias to delete} {--force}';

    protected $description = 'Delete a alias';

    protected array $site = [];

    public function handle()
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        try {
            $aliasName = $this->option('alias');
            if (empty($aliasName)) {
                $alias = $this->ploi->getAliases($serverId, $siteId)['data'];
                if (empty($alias)) {
                    error('No alias found on the selected site and server.');

                    return 1;
                }

                $aliasName = select(
                    label: 'Select the alias to delete:',
                    options: collect($alias['aliases'])->mapWithKeys(fn ($alias) => [$alias => $alias])->toArray(),
                    validate: fn ($value) => ! empty($value) ? null : 'Alias selection is required.',
                );
            }

            $this->warn('!! This action is irreversible !!');

            $confirm = $this->option('force') || text(
                label: 'Type the alias name to confirm deletion: '.$aliasName,
                validate: fn (string $value) => match (true) {
                    $value !== $aliasName => 'The alias name does not match.',
                    default => null,
                }
            );

            if (! $confirm) {
                $this->info('Alias deletion aborted.');

                return 0;
            }

            spin(
                callback: fn () => $this->ploi->deleteAlias($serverId, $siteId, $aliasName),
                message: 'Deleting alias...',
            );

            $this->success('Alias deleted successfully.');

        } catch (\Exception $e) {
            error('An error occurred while deleting the alias: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
