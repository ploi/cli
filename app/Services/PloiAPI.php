<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class PloiAPI
{
    public mixed $apiKey;

    public mixed $apiUrl;

    public mixed $headers;

    public function __construct()
    {
        $this->apiKey = config('ploi.token');
        $this->apiUrl = Config::get('ploi.api_url');
        $this->headers = [
            'User-Agent' => 'Ploi CLI',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    public function setToken(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function makeRequest(string $method, string $url, array $data = [], int $page = 1, ?string $search = null): array
    {
        $allData = [];
        $currentPage = $page;

        do {
            $response = $this->sendRequest($method, $url, $data, $currentPage, $search);

            if ($response->failed()) {
                $this->handleError($response);
            }

            $responseData = $response->json();

            // Return empty array for successful DELETE requests or empty responses
            if (empty($responseData) || ($method === 'delete' && $response->successful())) {
                return ['data' => []];
            }

            $allData = $this->mergeResponseData($allData, $responseData);

        } while ($this->hasNextPage($responseData, $currentPage++));

        return ['data' => $allData];
    }

    private function sendRequest(string $method, string $url, array $data, int $page, ?string $search): Response
    {
        $currentUrl = $this->buildUrl($url, $page, $search);
        $request = Http::withToken($this->apiKey)->withHeaders($this->headers);

        return match ($method) {
            'post', 'patch' => $request->$method($currentUrl, $data),
            default => $request->$method($currentUrl)
        };
    }

    private function buildUrl(string $url, int $page, ?string $search): string
    {
        $params = ['page' => $page];
        if ($search) {
            $params['search'] = $search;
        }

        return $url.'?'.http_build_query($params);
    }

    private function handleError(Response $response): void
    {
        match ($response->status()) {
            404 => exit('Resource not found.'),
            422, 403, 401 => $this->handleValidationError($response),
            default => $response
        };
    }

    private function handleValidationError(Response $response): never
    {
        $error = $response->json()['error']
            ?? $response->json()['errors']
            ?? $response->json()['message']
            ?? null;

        $errorMessage = "\033[31m ==> \033[0m\033[1;37m";

        if ($error === 'Unauthenticated.') {
            $errorMessage .= "The given API key is incorrect. Use 'ploi token --force' to set a new one.";
        } elseif (is_string($error)) {
            $errorMessage .= $error;
        } elseif (is_array($error)) {
            foreach ($error as $e) {
                $errorMessage .= is_array($e) ? Arr::first($e) : $e;
                $errorMessage .= ' ';
            }
        }

        exit($errorMessage."\033[0m\n");
    }

    private function mergeResponseData(array $existing, array $new): array
    {
        if (empty($new)) {
            return $existing;
        }

        $newData = $new['data'] ?? [$new];

        return array_merge($existing, $newData);
    }

    private function hasNextPage(array $responseData, int $currentPage): bool
    {
        return isset($responseData['meta']) &&
            $currentPage <= $responseData['meta']['last_page'];
    }

    /**
     * Server Methods
     */
    public function getServerList($page = 1, $search = null)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers', page: $page, search: $search);
    }

    public function createServer($data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers', $data);
    }

    public function getServerDetails($serverId, $page = 1)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId, [], $page);
    }

    public function updateServer($serverId, $data)
    {
        return $this->makeRequest('patch', $this->apiUrl.'/servers/'.$serverId, $data);
    }

    public function deleteServer($serverId)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId);
    }

    public function createServerUser($serverId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/system-users', $data);
    }

    public function getSystemUsers($serverId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/system-users');
    }

    public function restartServer($serverId)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/restart');
    }

    public function restartService($serverId, $service)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/services/'.$service.'/restart');
    }

    public function enableOPcache($serverId)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/enable-opcache');
    }

    public function disableOPcache($serverId)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/disable-opcache');
    }

    public function refreshOPcache($serverId)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/refresh-opcache');
    }

    public function databaseList($serverId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/databases');
    }

    public function createDatabase($serverId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/databases', $data);
    }

    public function acknowledgeDatabase($serverId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/databases/acknowledge', $data);
    }

    public function forgetDatabase($serverId, $databaseId)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/databases/'.$databaseId.'/forget');
    }

    public function duplicateDatabase($serverId, $databaseId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/databases/'.$databaseId.'/duplicate', $data);
    }

    public function deleteDatabase($serverId, $databaseId)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/databases/'.$databaseId);
    }

    public function createDatabaseUser($serverId, $databaseId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/databases/'.$databaseId.'/users', $data);
    }

    public function getServerLogs($serverId, $page = 1)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/logs', [], $page);
    }

    public function getServerLog($serverId, $logId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/logs/'.$logId);
    }

    public function getCronjobs($serverId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/crontabs');
    }

    public function createCronjob($serverId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/crontabs', $data);
    }

    public function deleteCronjob($serverId, $cronjobId)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/crontabs/'.$cronjobId);
    }

    public function getNetworkRules($serverId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/network-rules');
    }

    public function createNetworkRule($serverId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/network-rules', $data);
    }

    public function deleteNetworkRule($serverId, $ruleId)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/network-rules/'.$ruleId);
    }

    public function getDaemons($serverId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/daemons');
    }

    public function createDaemon($serverId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/daemons', $data);
    }

    public function pauseDaemon($serverId, $daemonId)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/daemons/'.$daemonId.'/toggle-pause');
    }

    public function restartDaemon($serverId, $daemonId)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/daemons/'.$daemonId.'/restart');
    }

    public function deleteDaemon($serverId, $daemonId)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/daemons/'.$daemonId);
    }

    public function getInsights($serverId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/insights');
    }

    public function fixInsight($serverId, $insightId)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/insights/'.$insightId.'/automatically-fix');
    }

    public function ignoreInsight($serverId, $insightId)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/insights/'.$insightId.'/ignore');
    }

    /**
     * Site Methods
     */
    public function getSiteList($serverId, $page = 1, $search = null)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites', [], $page, $search);
    }

    public function createSite($serverId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites', $data);
    }

    public function getSiteDetails($serverId, $siteId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId);
    }

    public function updateSite($serverId, $siteId, $data)
    {
        return $this->makeRequest('patch', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId, $data);
    }

    public function deleteSite($serverId, $siteId)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId);
    }

    public function installRepository($serverId, $siteId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/repository', $data);
    }

    public function getRepository($serverId, $siteId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/repository');
    }

    public function deleteRepository($serverId, $siteId)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/repository');
    }

    public function getTestDomain($serverId, $siteId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/test-domain');
    }

    public function enableTestDomain($serverId, $siteId)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/test-domain');
    }

    public function disableTestDomain($serverId, $siteId)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/test-domain');
    }

    public function deploySite($serverId, $siteId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/deploy', $data);
    }

    public function deployToProduction($serverId, $siteId)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/deploy-to-production');
    }

    public function suspendSite($serverId, $siteId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/suspend', $data);
    }

    public function resumeSite($serverId, $siteId)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/resume');
    }

    public function getSiteLogs($serverId, $siteId, $page = 1)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/log', [], $page);
    }

    public function getSiteLog($serverId, $siteId, $logId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/log/'.$logId);
    }

    public function getEnv($serverId, $siteId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/env');
    }

    public function updateEnv($serverId, $siteId, $data)
    {
        return $this->makeRequest('patch', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/env', $data);
    }

    public function getCertificates($serverId, $siteId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/certificates');
    }

    public function getCertificateDetails($serverId, $siteId, $certId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/certificates/'.$certId);
    }

    public function createCertificate($serverId, $siteId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/certificates', $data);
    }

    public function deleteCertificate($serverId, $siteId, $certId)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/certificates/'.$certId);
    }

    public function getRedirects($serverId, $siteId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/redirects');
    }

    public function createRedirect($serverId, $siteId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/redirects', $data);
    }

    public function getRedirectDetails($serverId, $siteId, $redirectId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/redirects/'.$redirectId);
    }

    public function deleteRedirect($serverId, $siteId, $redirectId)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/redirects/'.$redirectId);
    }

    public function getTenants($serverId, $siteId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/tenants');
    }

    public function createTenant($serverId, $siteId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/tenants', $data);
    }

    public function deleteTenant($serverId, $siteId, $tenant)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/tenants/'.$tenant);
    }

    public function requestCertificateTenant($serverId, $siteId, $tenant, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/tenants/'.$tenant.'/request-certificate', $data);
    }

    public function revokeCertificateTenant($serverId, $siteId, $tenant, $data)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/tenants/'.$tenant.'/revoke-certificate', $data);
    }

    public function getAliases($serverId, $siteId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/aliases');
    }

    public function createAlias($serverId, $siteId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/aliases', $data);
    }

    public function deleteAlias($serverId, $siteId, $alias)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/aliases/'.$alias);
    }

    public function resetSitePermissions($serverId, $siteId)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/permission-reset');
    }

    public function getAuthUsers($serverId, $siteId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/auth-users');
    }

    public function createAuthUser($serverId, $siteId, $data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/auth-users', $data);
    }

    public function deleteAuthUser($serverId, $siteId, $authUserId)
    {
        return $this->makeRequest('delete', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/auth-users/'.$authUserId);
    }

    /**
     * User Methods
     */
    public function checkUser()
    {
        return $this->makeRequest('get', $this->apiUrl.'/user');
    }

    public function getProviders()
    {
        return $this->makeRequest('get', $this->apiUrl.'/user/server-providers');
    }
}
