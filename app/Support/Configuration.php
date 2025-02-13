<?php

namespace App\Support;

use Exception;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Yaml;

class Configuration
{
    protected array $configs = [];

    protected array $configFiles = [
        'settings' => 'settings.yml',
        'provision' => 'provision.yml',
    ];

    public function __construct()
    {
        foreach ($this->configFiles as $type => $file) {
            try {
                $content = Yaml::parseFile(getcwd().'/.ploi/'.$file);
                // Extract the inner content if it's wrapped in a type key
                $this->configs[$type] = $content[$type] ?? $content;
            } catch (Exception $e) {
                $this->configs[$type] = [];
            }
        }
    }

    public function initialize(string $server, string $site, string $path, string $domain): void
    {
        $this->configs['settings'] = $this->getSettingsFormat($server, $site, $domain);
        $this->configs['provision'] = $this->getProvisionFormat();

        foreach ($this->configFiles as $type => $file) {
            $this->store($path.'/.ploi/'.$file, $type);
        }
    }

    protected function getSettingsFormat(string $server, string $site, string $domain): array
    {
        return [
            'site' => $site,
            'server' => $server,
            'domain' => $domain,
        ];
    }

    protected function getProvisionFormat(): array
    {
        return [
            'commands' => [],
            'services' => [],
        ];
    }

    public function store(string $configFile, string $type): void
    {
        $directory = dirname($configFile);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $wrappedConfig = [
            $type => $this->configs[$type],
        ];

        $configContent = Yaml::dump(
            $wrappedConfig,
            4,
            2,
            Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE
        );

        file_put_contents($configFile, $configContent);
    }

    public function get(string $key, $default = null)
    {
        // If the key is just a config type, return the entire config
        if (isset($this->configFiles[$key])) {
            return $this->configs[$key];
        }

        $parts = explode('.', $key, 2);
        if (count($parts) !== 2 || ! isset($this->configFiles[$parts[0]])) {
            throw new \InvalidArgumentException(
                "Key must be a valid config type or start with valid config type (e.g., 'settings', 'settings.server', 'provision.commands')"
            );
        }

        [$type, $key] = $parts;

        return Arr::get($this->configs[$type], $key, $default);
    }

    public function set(string $key, $value): void
    {
        $parts = explode('.', $key, 2);
        if (count($parts) !== 2 || ! isset($this->configFiles[$parts[0]])) {
            throw new \InvalidArgumentException(
                "Key must start with valid config type (e.g., 'settings.server', 'provision.commands')"
            );
        }

        [$type, $key] = $parts;
        Arr::set($this->configs[$type], $key, $value);
    }

    public function addConfigFile(string $type, string $filename): void
    {
        $this->configFiles[$type] = $filename;
        try {
            $content = Yaml::parseFile(getcwd().'/.ploi/'.$filename);
            // Extract the inner content if it's wrapped in a type key
            $this->configs[$type] = $content[$type] ?? $content;
        } catch (Exception $e) {
            $this->configs[$type] = [];
        }
    }

    public function getConfigTypes(): array
    {
        return array_keys($this->configFiles);
    }
}
