<?php

namespace App\Commands\Site\Tenant;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class CreateTenantCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'tenant:create {--server=} {--site=}';

    protected $description = 'Add a tenant (or more) to your site';

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();

        $tenants = text(
            label: 'Enter the tenants, separated by comma:',
            required: true,
            validate: fn (string $value) => match (true) {
                strlen($value) <= 0 => 'Please enter at least one domain.',
                default => $this->validateDomains($value),
            },
            hint: 'e.g. example.com, anotherdomain.com'
        );

        $tenants = explode(',', $tenants);

        $data = $this->ploi->createTenant($serverId, $siteId, [
            'tenants' => $tenants
        ]);

        if($data){
            $this->success('Tenants created successfully');
        }

    }

    function validateDomains(string $value): ?string {
        $domains = array_map('trim', explode(',', $value));

        foreach ($domains as $domain) {
            if (!preg_match('/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/', $domain)) {
                return "Invalid domain format: '$domain'. Please use format like example.com";
            }
        }

        return null;
    }
}
