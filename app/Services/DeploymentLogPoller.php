<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class DeploymentLogPoller
{
    private $apiKey;

    private $baseUrl;

    public function __construct(string $apiKey, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl ?? config('ploi.api_url');
    }

    /**
     * Poll deployment logs and stream new lines as they appear
     *
     * @param  callable|null  $onNewLines  Callback for each new log line
     *
     * @throws Exception
     */
    public function pollDeploymentLogs(int $serverId, int $siteId, int $deploymentId, ?callable $onNewLines = null): void
    {
        $isActive = true;
        $lastLogPosition = 0;
        $pollInterval = 2; // seconds
        $maxRetries = 3;
        $retryCount = 0;
        $maxPollAttempts = 300; // 10 minutes at 2-second intervals
        $pollAttempts = 0;

        while ($isActive && $pollAttempts < $maxPollAttempts) {
            try {
                $response = $this->getDeploymentLog($serverId, $siteId, $deploymentId);

                if ($response && isset($response['content'])) {
                    $newLines = $this->extractNewLines($response['content'], $lastLogPosition);

                    if (! empty($newLines)) {
                        foreach ($newLines as $line) {
                            if (trim($line) !== '') { // Skip empty lines
                                if ($onNewLines) {
                                    $onNewLines($line);
                                } else {
                                    echo $line.PHP_EOL;
                                }
                            }
                        }

                        $lastLogPosition += count($newLines);
                    }
                }

                // Check deployment status to see if we should stop polling
                $deployment = $this->getDeployment($serverId, $siteId, $deploymentId);
                if ($deployment && ! in_array($deployment['status'], ['pending', 'running', 'deploying'])) {
                    $isActive = false;

                    // Show final status
                    if ($onNewLines) {
                        $onNewLines("Deployment {$deployment['status']}");
                    }
                }

                if ($isActive) {
                    sleep($pollInterval);
                    $pollAttempts++;
                }

                // Reset retry count on successful poll
                $retryCount = 0;

            } catch (Exception $e) {
                $retryCount++;

                if ($retryCount >= $maxRetries) {
                    throw new Exception('Max retries reached. Last error: '.$e->getMessage());
                }

                if ($onNewLines) {
                    $onNewLines("Error polling logs (retry {$retryCount}/{$maxRetries}): ".$e->getMessage());
                } else {
                    echo "Error polling logs (retry {$retryCount}/{$maxRetries}): ".$e->getMessage().PHP_EOL;
                }

                sleep(5); // Wait longer on error
            }
        }

        if ($pollAttempts >= $maxPollAttempts) {
            $message = 'Log polling timeout reached (10 minutes). Deployment may still be in progress.';
            if ($onNewLines) {
                $onNewLines($message);
            } else {
                echo $message.PHP_EOL;
            }
        }
    }

    /**
     * Extract new lines from log content based on last position
     */
    private function extractNewLines(string $content, int $lastPosition): array
    {
        $lines = explode("\n", $content);

        if ($lastPosition < count($lines)) {
            return array_slice($lines, $lastPosition);
        }

        return [];
    }

    /**
     * Get deployment log content
     *
     * @throws Exception
     */
    private function getDeploymentLog(int $serverId, int $siteId, int $deploymentId): ?array
    {
        $response = $this->makeApiRequest("servers/{$serverId}/sites/{$siteId}/deployments/{$deploymentId}/log");

        return $response['data'] ?? null;
    }

    /**
     * Get deployment status
     *
     * @throws Exception
     */
    private function getDeployment(int $serverId, int $siteId, int $deploymentId): ?array
    {
        $response = $this->makeApiRequest("servers/{$serverId}/sites/{$siteId}/deployments");

        $deployments = $response['data'] ?? [];

        foreach ($deployments as $deployment) {
            if ($deployment['id'] == $deploymentId) {
                return $deployment;
            }
        }

        return null;
    }

    /**
     * Make authenticated API request
     *
     * @throws Exception
     */
    private function makeApiRequest(string $endpoint): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Accept' => 'application/json',
            'User-Agent' => 'Ploi CLI',
        ])->get($this->baseUrl.'/'.$endpoint);

        if (! $response->successful()) {
            throw new Exception('API request failed: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Get the latest deployment for a site
     *
     * @throws Exception
     */
    public function getLatestDeployment(int $serverId, int $siteId): ?array
    {
        $response = $this->makeApiRequest("servers/{$serverId}/sites/{$siteId}/deployments?per_page=1");

        $deployments = $response['data'] ?? [];

        return $deployments[0] ?? null;
    }

    /**
     * Check if there's an active deployment
     *
     * @throws Exception
     */
    public function getActiveDeployment(int $serverId, int $siteId): ?array
    {
        $response = $this->makeApiRequest("servers/{$serverId}/sites/{$siteId}/deployments");

        $deployments = $response['data'] ?? [];

        foreach ($deployments as $deployment) {
            if (in_array($deployment['status'], ['pending', 'running', 'deploying'])) {
                return $deployment;
            }
        }

        return null;
    }

    /**
     * Get all deployments for a site
     *
     * @throws Exception
     */
    public function getDeployments(int $serverId, int $siteId): array
    {
        $response = $this->makeApiRequest("servers/{$serverId}/sites/{$siteId}/deployments");

        return $response['data'] ?? [];
    }
}
