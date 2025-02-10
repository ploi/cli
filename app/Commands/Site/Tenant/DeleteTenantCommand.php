<?php

namespace App\Commands\Site\Tenant;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class DeleteTenantCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'tenant:delete {--server=} {--site=} {--tenant= : The name of the tenant to delete} {--force}';

    protected $description = 'Delete a tenant';

    protected array $site = [];

    public function handle()
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        try {
            $tenantName = $this->option('tenant');
            if (empty($tenantName)) {
                $tenants = $this->ploi->getTenants($serverId, $siteId)['data'];
                if (empty($tenants)) {
                    error('No tenants found on the selected site and server.');

                    return 1;
                }

                $tenantName = select(
                    label: 'Select the tenant to delete:',
                    options: collect($tenants['tenants'])->mapWithKeys(fn ($tenant) => [$tenant => $tenant])->toArray(),
                    validate: fn ($value) => ! empty($value) ? null : 'Tenant selection is required.',
                );
            }

            $this->warn('!! This action is irreversible !!');

            $confirm = $this->option('force') || text(
                    label: 'Type the tenant name to confirm deletion: '.$tenantName,
                    validate: fn (string $value) => match (true) {
                        $value !== $tenantName => 'The tenant name does not match.',
                        default => null,
                    }
                );

            if (! $confirm) {
                $this->info('Tenant deletion aborted.');

                return 0;
            }

            spin(
                callback: fn () => $this->ploi->deleteTenant($serverId, $siteId, $tenantName),
                message: 'Deleting tenant...',
            );

            $this->success('Tenant deleted successfully.');

        } catch (\Exception $e) {
            error('An error occurred while deleting the tenant: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
