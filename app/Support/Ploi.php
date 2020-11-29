<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class Ploi
{

    protected static $BASE_URL = "https://ploi.io/api/";

    private $token;

    /**
     * Ploi constructor.
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function getServers()
    {
        $response = Http::withToken($this->token)->get(self::$BASE_URL . 'servers');

        return $response['data'];
    }

    public function getServerProviders()
    {
        return Http::withToken($this->token)->get(self::$BASE_URL . "user/server-providers")['data'];
    }

    public function getSites($server)
    {
        return Http::withToken($this->token)->get(self::$BASE_URL . "servers/{$server}/sites")['data'];
    }

    public function createSite($server, $rootDomain, $webDir, $systemUser): int
    {
        $response = Http::withToken($this->token)->post(self::$BASE_URL . "servers/{$server}/sites", [
            'root_domain' => $rootDomain,
            'web_directory' => $webDir,
            'system_user' => $systemUser
        ]);

        return $response['data']['id'];
    }

    public function installRepository($server, $site, $provider, $branch, $repo)
    {
        $response = Http::withToken($this->token)->post(self::$BASE_URL . "servers/{$server}/sites/{$site}/repository", [
            'provider' => $provider,
            'branch' => $branch,
            'name' => $repo
        ]);

        return $response->status() == 200;
    }

    public function getEnvironmentFile($server, $site)
    {
        return Http::withToken($this->token)->get(self::$BASE_URL . "servers/{$server}/sites/{$site}/env")['data'];
    }

    public function pushEnvironmentFile($server, $site, $contents)
    {
        $response = Http::withToken($this->token)->patch(self::$BASE_URL . "servers/{$server}/sites/{$site}/env", [
            'content' => $contents
        ]);

        return $response->status() == 200;
    }

    public function getDeployScript($server, $site)
    {
        return Http::withToken($this->token)->get(self::$BASE_URL . "servers/{$server}/sites/{$site}/deploy/script")['deploy_script'];
    }

    public function pushDeployScript($server, $site, $contents)
    {
        $response = Http::withToken($this->token)->patch(self::$BASE_URL . "servers/{$server}/sites/{$site}/deploy/script", [
            'deploy_script' => $contents
        ]);

        return $response->status() == 200;
    }

    public function deploy($server, $site)
    {
        return Http::withToken($this->token)->post(self::$BASE_URL . "servers/{$server}/sites/{$site}/deploy")->status() == 200;
    }

    public function certificates($server, $site)
    {
        return Http::withToken($this->token)->get(self::$BASE_URL . "servers/{$server}/sites/{$site}/certificates")['data'];
    }

    public function secure($server, $site, $domains)
    {
        return Http::withToken($this->token)->post(self::$BASE_URL . "servers/{$server}/sites/{$site}/certificates", [
            'certificate' => $domains,
            'type' => 'letsencrypt'
        ]);
    }

    public function unsecure($server, $site, $certificate)
    {
        return Http::withToken($this->token)->delete(self::$BASE_URL . "servers/{$server}/sites/{$site}/certificates/{$certificate}")->status() == 200;
    }

    public function deleteSite($server, $site)
    {
        return Http::withToken($this->token)->delete(self::$BASE_URL . "servers/{$server}/sites/{$site}")->status() == 200;
    }

    public function createServer($name, $plan, $region, $credential, $type, $databaseType, $webserverType, $phpVersion)
    {
        return Http::withToken($this->token)->post(self::$BASE_URL . "servers", [
                'name' => $name,
                'plan' => $plan,
                'region' => $region,
                'credential' => $credential,
                'type' => $type,
                'database_type' => $databaseType,
                'webserver_type' => $webserverType,
                'php_version' => $phpVersion
            ])->status() === 200;
    }

    public function getDatabases($server)
    {
        return Http::withToken($this->token)->get(self::$BASE_URL . "servers/{$server}/databases")['data'];
    }

    public function createDatabase($server, $name, $user, $password)
    {
        return Http::withToken($this->token)->post(self::$BASE_URL . "servers/{$server}/databases", [
                'name' => $name,
                'user' => $user,
                'password' => $password
            ])->status() === 200;
    }

    public function deleteDatabase($server, $database)
    {
        return Http::withToken($this->token)->delete(self::$BASE_URL . "servers/{$server}/databases/{$database}")->status() == 200;
    }

    public function enableTestDomain($server, $site)
    {
        return Http::withToken($this->token)->post(self::$BASE_URL . "servers/{$server}/sites/{$site}/test-domain")['data'];
    }

    public function disableTestDomain($server, $site)
    {
        return Http::withToken($this->token)->delete(self::$BASE_URL . "servers/{$server}/sites/{$site}/test-domain")->status() == 200;
    }

    public function getLogs($server, $site)
    {
        return Http::withToken($this->token)->get(self::$BASE_URL . "servers/{$server}/sites/{$site}/log")['data'];
    }

    public function getLog($server, $site, $log)
    {
        return Http::withToken($this->token)->get(self::$BASE_URL . "servers/{$server}/sites/{$site}/log/{$log}")['data'];
    }

}
