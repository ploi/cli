<?php

namespace App\Commands\Site\Redirect;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class CreateRedirectCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'redirect:create {--server=} {--site=} {--from=} {--to=} {--type=}';

    protected $description = 'Create a site redirect.';

    protected array $site = [];

    public function handle(): void
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        $from = $this->option('from') ?? text(
            label: 'Enter the path you want to redirect from:',
            default: '/',
            required: true,
            validate: fn (string $value) => match (true) {
                $value[0] !== '/' => 'The redirect from must start with a forward slash.',
                default => null,
            },
            hint: 'The redirect from must start with "/"'
        );

        $to = $this->option('to') ?? text(
            label: 'Enter the URL or path you want to redirect to:',
            required: true,
            validate: fn (string $value) => match (true) {
                $value[0] !== '/' && ! filter_var($value, FILTER_VALIDATE_URL) => 'The redirect to must start with a forward slash or be a valid URL.',
                default => null,
            },
        );

        $type = $this->option('type') ?? select('Select the type of redirect:',
            options: [
                'redirect' => 'Temporary (302)',
                'permanent' => 'Permanent (301)',
            ]
        );

        $data = [
            'redirect_from' => $from,
            'redirect_to' => $to,
            'type' => $type,
        ];

        $redirect = $this->ploi->createRedirect($serverId, $siteId, $data)['data'];
        $statusCheck = spin(
            callback: function () use ($serverId, $siteId, $redirect) {
                while (true) {
                    sleep(5);

                    $status = $this->ploi->getRedirectDetails($serverId, $siteId, $redirect['id'])['data']['status'] ?? 'created';

                    $statusMap = [
                        'active' => ['type' => 'success', 'message' => "Redirect from '{$redirect['redirect_from']}' to '{$redirect['redirect_to']}' created successfully."],
                        'failed' => ['type' => 'error', 'message' => 'Redirect creation failed. Please check manually.'],
                    ];

                    return $statusMap[$status] ?? ['type' => 'warn', 'message' => 'Redirect status is unknown. Please check manually.'];
                }
            },
            message: 'Creating redirect...'
        );

        $this->console($statusCheck['message'], $statusCheck['type']);
    }
}
