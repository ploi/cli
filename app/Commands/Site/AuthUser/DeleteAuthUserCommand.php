<?php

namespace App\Commands\Site\AuthUser;

use App\Commands\Command;
use App\Commands\Concerns\InteractWithServer;
use App\Commands\Concerns\InteractWithSite;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class DeleteAuthUserCommand extends Command
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer, InteractWithSite;

    protected $signature = 'auth-user:delete {--server=} {--site=} {--id= : The id of the auth user to delete} {--force}';

    protected $description = 'Deletes an auth user in site';

    protected array $site = [];

    public function handle()
    {
        $this->ensureHasToken();

        [$serverId, $siteId] = $this->getServerAndSite();
        $this->site = $this->ploi->getSiteDetails($serverId, $siteId)['data'];

        try {
            $id = $this->option('id');
            if (empty($id)) {
                $authUsers = $this->ploi->getAuthUsers($serverId, $siteId)['data'];
                if (empty($authUsers)) {
                    error('No authentication users found on the selected site and server.');

                    return 1;
                }

                $id = select(
                    label: 'Select the authentication user to delete:',
                    options: collect($authUsers)->mapWithKeys(fn ($user) => [
                        $user['id'] => "[{$user['id']}] {$user['name']} ({$user['path']})",
                    ])->toArray(),
                    validate: fn ($value) => ! empty($value) ? null : 'User selection is required.',
                );
            }

            $this->warn('!! This action is irreversible !!');

            $confirm = $this->option('force') || confirm(
                label: 'Are you sure you want to delete this authentication user?',
                default: false
            );

            if (! $confirm) {
                $this->info('Authentication user deletion aborted.');

                return 0;
            }

            spin(
                callback: fn () => $this->ploi->deleteAuthUser($serverId, $siteId, $id),
                message: 'Deleting authentication user...',
            );

            $this->success('Authentication user deleted successfully.');

        } catch (\Exception $e) {
            error('An error occurred while deleting the authentication user: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
