<?php

namespace App\Support;

use Exception;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Yaml;

class Configuration
{
    /** @var array */
    protected mixed $config;

    public function __construct()
    {
        try {
            $this->config = Yaml::parseFile(getcwd().'/ploi.yml');
        } catch (Exception $e) {
            $this->config = [];
        }
    }

    public function initialize(string $server, string $site, string $path, string $domain): void
    {
        $configFile = $path.'/ploi.yml';

        $this->config = $this->getConfigFormat($server, $site, $domain);

        $this->store($configFile);
    }

    protected function getConfigFormat(string $server, string $site, string $domain): array
    {
        return [
            'site' => $site,
            'server' => $server,
            'domain' => $domain,
        ];
    }

    public function store(string $configFile): void
    {
        $configContent = Yaml::dump($this->config, 4, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

        file_put_contents($configFile, $configContent);
    }

    public function get(string $key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }

    public function set(string $key, $value): void
    {
        Arr::set($this->config, $key, $value);
    }
}
