<?php

namespace App\Commands\Server\Cronjob;

use App\Commands\Command as BaseCommand;
use App\Commands\Concerns\InteractWithServer;
use App\Traits\EnsureHasToken;
use App\Traits\HasPloiConfiguration;

use function Laravel\Prompts\form;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateCronjobCommand extends BaseCommand
{
    use EnsureHasToken, HasPloiConfiguration, InteractWithServer;

    protected $signature = 'cronjob:create {--server=} {--user=} {--command=} {--frequency=}';

    protected $description = 'Create a cronjob on a server';

    public function handle(): void
    {
        $this->ensureHasToken();

        $serverId = $this->getServerId();

        // Check if all options are provided
        $options = [
            'server' => $this->option('server'),
            'user' => $this->option('user'),
            'command' => $this->option('command'),
            'frequency' => $this->option('frequency'),
        ];

        if (array_filter($options)) {
            $responses = $options;
        } else {

            $responses = form()
                ->text(
                    label: 'Which user should the cronjob run?',
                    placeholder: 'ploi',
                    required: true,
                    name: 'user'
                )
                ->text(
                    label: 'What command should the cronjob run?',
                    placeholder: 'php /home/ploi/default/artisan schedule:run',
                    name: 'command'
                )
                ->add(function () {
                    $frequency = select(
                        label: 'What frequency should the cronjob run at?',
                        options: [
                            '* * * * *' => 'Every minute',
                            '0 * * * *' => 'Hourly',
                            '0 2 * * *' => 'Nightly (2am)',
                            '0 0 * * 0' => 'Weekly',
                            '0 0 1 * *' => 'Monthly',
                            'custom' => 'Custom',
                        ],
                        scroll: 6,
                    );

                    if ($frequency === 'custom') {
                        return text(
                            label: 'What frequency should the cronjob run at?',
                            placeholder: '* * * * *',
                            required: true,
                            validate: fn (string $value) => match (true) {
                                ! preg_match(
                                    '/^(\*|([0-5]?\d)(-[0-5]?\d)?(\/\d+)?(,([0-5]?\d)(-[0-5]?\d)?(\/\d+)?)*)\s+'.
                                    '(\*|([01]?\d|2[0-3])(-([01]?\d|2[0-3]))?(\/\d+)?(,([01]?\d|2[0-3])(-([01]?\d|2[0-3]))?(\/\d+)?)*)\s+'.
                                    '(\*|([1-9]|[12]\d|3[01])(-([1-9]|[12]\d|3[01]))?(\/\d+)?(,([1-9]|[12]\d|3[01])(-([1-9]|[12]\d|3[01]))?(\/\d+)?)*)\s+'.
                                    '(\*|(1[0-2]|0?[1-9])(-(1[0-2]|0?[1-9]))?(\/\d+)?(,(1[0-2]|0?[1-9])(-(1[0-2]|0?[1-9]))?(\/\d+)?)*)\s+'.
                                    '(\*|([0-7])(-([0-7]))?(\/\d+)?(,([0-7])(-([0-7]))?(\/\d+)?)*)$/', $value) => 'Invalid cronjob frequency',
                                default => null
                            },
                            hint: 'min | hour | day/month | month | day/week',
                        );
                    }

                    return $frequency;
                }, name: 'frequency')
                ->submit();
        }

        $cron = $this->ploi->createCronjob($serverId, $responses)['data'] ?? null;

        if ($cron['status'] === 'error') {
            $this->error($cron['message'] ?? 'An error occurred, but no specific message was provided.');
            exit(1);
        }

        $this->success('Cronjob created successfully.');
    }
}
