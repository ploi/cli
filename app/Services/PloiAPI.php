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

    public function makeRequest($method, $url, $data = [], $page = 1)
    {
        $response = Http::withToken($this->apiKey)
            ->withHeaders(['User-Agent' => 'Ploi CLI'])
            ->$method($url, $data);

        if ($response->failed()) {
            return $response;
        }

        $responseData = $response->json();
        if (isset($responseData['meta']['pagination'])) {
            $pagination = $responseData['meta']['pagination'];
            $totalPages = $pagination['total_pages'];
            $currentPage = $pagination['current_page'];

            if ($currentPage < $totalPages) {
                $nextPageUrl = $url.'?page='.($currentPage + 1);
                $responseData['next_page_url'] = $nextPageUrl;
            }

            if ($currentPage > 1) {
                $prevPageUrl = $url.'?page='.($currentPage - 1);
                $responseData['prev_page_url'] = $prevPageUrl;
            }
        }

        return $responseData;
    }

    /**
     * Server Methods
     */
    public function getServerList($page = 1)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers', [], $page);
    }

    public function createServer($data)
    {
        return $this->makeRequest('post', $this->apiUrl.'/servers', $data);
    }

    public function getServerDetails($serverId)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId);
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

    /**
     * Site Methods
     */
    public function getSiteList($serverId, $page = 1)
    {
        return $this->makeRequest('get', $this->apiUrl.'/servers/'.$serverId.'/sites', [], $page);
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

    /**
     * User Methods
     */
    public function checkUser()
    {
        return $this->makeRequest('get', $this->apiUrl.'/user');
    }
}
