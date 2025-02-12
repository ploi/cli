<?php

namespace App\Commands\Site\Alias;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class CreateAliasCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'alias:create {--server=} {--site=}';

    protected $description = 'Add a alias (or more) to your site';

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();

        $alias = text(
            label: 'Enter the alias, separated by comma:',
            required: true,
            validate: fn (string $value) => match (true) {
                strlen($value) <= 0 => 'Please enter at least one domain.',
                default => $this->validateDomains($value),
            },
            hint: 'e.g. example.com, anotherdomain.com'
        );

        $alias = explode(',', $alias);

        $data = $this->ploi->createAlias($serverId, $siteId, [
            'aliases' => $alias
        ]);

        if($data){
            $this->success('Alias created successfully');
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
