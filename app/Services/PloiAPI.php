<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class PloiAPI
{
    public mixed $apiKey;

    public mixed $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('ploi.token');
        $this->apiUrl = Config::get('ploi.api_url');
    }

    public function setToken(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function makeRequest($method, $url, $data = [], $page = 1, $search = null)
    {
        $queryParams = ['page' => $page];
        if (! is_null($search)) {
            $queryParams['search'] = $search;
        }
        $url .= '?'.http_build_query($queryParams);

        $request = Http::withToken($this->apiKey)
            ->withHeaders(['User-Agent' => 'Ploi CLI']);

        $response = $method === 'post'
            ? $request->$method($url, $data)
            : $request->$method($url);

        if ($response->status() === 404) {
            return exit('Resource not found.');
        }

        if ($response->status() === 422) {
            $errors = $response->json()['errors'];
            $errorMessage = "\033[31m".' ==> '."\033[0m\033[1;37;40m";
            foreach ($errors as $error) {
                $errorMessage .= is_array($error) ? json_encode($error).' ' : $error.' ';
            }
            $errorMessage .= "\033[0m";

            return exit($errorMessage);
        }

        if ($response->failed()) {
            return $response;
        }

        $responseData = $response->json();
        if (isset($responseData['meta']['pagination'])) {
            $pagination = $responseData['meta']['pagination'];
            $totalPages = $pagination['total_pages'];
            $currentPage = $pagination['current_page'];

            if ($currentPage < $totalPages) {
                $nextPageUrl = $url.'&page='.($currentPage + 1);
                $responseData['next_page_url'] = $nextPageUrl;
            }

            if ($currentPage > 1) {
                $prevPageUrl = $url.'&page='.($currentPage - 1);
                $responseData['prev_page_url'] = $prevPageUrl;
            }
        }

        return $responseData;
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

    public function restartServer($serverId)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/restart');
    }

    public function restartService($serverId, $service)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/services/'.$service.'/restart');
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

    public function enableTestDomain($serverId, $siteId)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers/'.$serverId.'/sites/'.$siteId.'/test-domain');
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

    /**
     * User Methods
     */
    public function checkUser()
    {
        return $this->makeRequest('get', $this->apiUrl.'/user');
    }
}
