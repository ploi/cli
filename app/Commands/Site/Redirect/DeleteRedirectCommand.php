<?php

namespace App\Commands\Site\Redirect;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class DeleteRedirectCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'redirect:delete {--server=} {--site=} {--redirect-id= : The ID of the redirect to delete}';

    protected $description = 'Delete a redirect';

    protected array $site = [];

    protected array $type = [
        'permanent' => 'Permanent (301)',
        'redirect' => 'Temporary (302)',
    ];

    public function handle()
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        try {
            $redirectId = $this->option('redirect-id');
            if (empty($redirectId)) {
                $redirects = $this->ploi->getRedirects($serverId, $siteId)['data'];
                if (empty($redirects)) {
                    error('No redirect found on the selected site and server.');

                    return 1;
                }

                ray($redirects);

                $redirectId = select(
                    label: 'Select the redirect to delete:',
                    options: collect($redirects)->mapWithKeys(fn ($redirect) => [$redirect['id'] => $redirect['redirect_from'].' => '.$redirect['redirect_to'].' ('.$this->type[$redirect['type']].')'])->toArray(),
                    validate: fn ($value) => ! empty($value) ? null : 'Redirect selection is required.',
                );
            }

            spin(
                callback: fn () => $this->ploi->deleteRedirect($serverId, $siteId, $redirectId),
                message: 'Deleting redirect...',
            );

            $this->success('Redirect deleted successfully.');

        } catch (\Exception $e) {
            error('An error occurred while deleting the redirect: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
