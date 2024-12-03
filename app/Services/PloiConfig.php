<?php

namespace App\Services;

//class PloiConfig
//{
//    protected string $path;
//
//    public function __construct()
//    {
//        $this->path = self::path();
//    }
//
//
//    public static function path(): string
//    {
//        $path = isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] == 'testing'
//            ? base_path('tests')
//            : ($_SERVER['HOME'] ?? $_SERVER['USERPROFILE']);
//
//        $path .= '/.ploi/config.json';
//
//        return $path;
//    }
//
//    public static function get(string $key)
//    {
//        if (file_exists($this->path)) {
//            $config = json_decode(file_get_contents($path), true);
//            return $config[$key] ?? null;
//        }
//
//        return null;
//    }
//
//}
