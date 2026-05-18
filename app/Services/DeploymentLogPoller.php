<?php

namespace App\Services;

use Exception;

class DeploymentLogPoller
{
    private const POLL_INTERVAL = 2;

    private const MAX_POLL_ATTEMPTS = 300;

    private const MAX_RETRIES = 3;

    private const RETRY_BACKOFF = 5;

    private const FINAL_STATUSES = ['active', 'deploy-failed'];

    public function __construct(private readonly PloiAPI $ploi) {}

    /**
     * Poll the site endpoint, streaming new lines from `current_deploy_log` as they appear.
     *
     * Returns the site's final status (`active` / `deploy-failed`), or `null` on timeout.
     *
     * @param  callable|null  $onNewLines  Receives each new non-empty log line.
     *
     * @throws Exception
     */
    public function pollDeploymentLogs(int $serverId, int $siteId, ?callable $onNewLines = null): ?string
    {
        $lastLogPosition = 0;
        $hadLogContent = false;
        $retryCount = 0;
        $pollAttempts = 0;

        while ($pollAttempts < self::MAX_POLL_ATTEMPTS) {
            try {
                $site = $this->ploi->getSiteDetails($serverId, $siteId)['data'] ?? null;

                if (! $site) {
                    throw new Exception('Failed to fetch site details.');
                }

                $currentLog = $site['current_deploy_log'] ?? null;
                $status = $site['status'] ?? null;

                if ($currentLog !== null) {
                    $hadLogContent = true;
                    $lastLogPosition += $this->emitNewLines($currentLog, $lastLogPosition, $onNewLines);
                }

                // Deployment finished if the API reports a terminal status, or if it cleared
                // the log buffer after we had previously seen content.
                $finished = in_array($status, self::FINAL_STATUSES, true)
                    || ($hadLogContent && $currentLog === null);

                if ($finished) {
                    // The live `current_deploy_log` buffer is cleared the moment a deployment
                    // finishes, so its final lines never get polled. Emit anything we missed
                    // from the persisted deploy log before returning.
                    $lastLogPosition += $this->flushPersistedLog($serverId, $siteId, $lastLogPosition, $onNewLines);

                    return $status;
                }

                sleep(self::POLL_INTERVAL);
                $pollAttempts++;
                $retryCount = 0;
            } catch (Exception $e) {
                $retryCount++;

                if ($retryCount >= self::MAX_RETRIES) {
                    throw new Exception('Max retries reached. Last error: '.$e->getMessage());
                }

                $message = "Error polling logs (retry $retryCount/".self::MAX_RETRIES.'): '.$e->getMessage();

                if ($onNewLines) {
                    $onNewLines($message);
                } else {
                    echo $message.PHP_EOL;
                }

                sleep(self::RETRY_BACKOFF);
            }
        }

        $timeoutMessage = 'Log polling timeout reached (10 minutes). Deployment may still be in progress.';

        if ($onNewLines) {
            $onNewLines($timeoutMessage);
        } else {
            echo $timeoutMessage.PHP_EOL;
        }

        return null;
    }

    /**
     * Emit every non-empty line past `$lastPosition` and return how many lines were consumed.
     */
    private function emitNewLines(string $content, int $lastPosition, ?callable $onNewLines): int
    {
        $newLines = $this->extractNewLines($content, $lastPosition);

        foreach ($newLines as $line) {
            if (trim($line) === '') {
                continue;
            }

            if ($onNewLines) {
                $onNewLines($line);
            } else {
                echo $line.PHP_EOL;
            }
        }

        return count($newLines);
    }

    /**
     * Fetch the persisted deploy log and emit any lines the live buffer never streamed.
     *
     * Best-effort: a failure here does not affect the already-reported deployment status.
     */
    private function flushPersistedLog(int $serverId, int $siteId, int $lastPosition, ?callable $onNewLines): int
    {
        try {
            $logs = $this->ploi->getSiteLogs($serverId, $siteId, 1)['data'] ?? [];

            if (empty($logs)) {
                return 0;
            }

            $content = $this->ploi->getSiteLog($serverId, $siteId, $logs[0]['id'])['data']['content'] ?? null;

            if ($content === null) {
                return 0;
            }

            return $this->emitNewLines($content, $lastPosition, $onNewLines);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Slice lines past the last reported position.
     *
     * @return array<int, string>
     */
    private function extractNewLines(string $content, int $lastPosition): array
    {
        $lines = explode("\n", $content);

        if ($lastPosition < count($lines)) {
            return array_slice($lines, $lastPosition);
        }

        return [];
    }
}
