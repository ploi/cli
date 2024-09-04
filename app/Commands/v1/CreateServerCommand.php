<?php

namespace App\Commands\v1;

use App\Concerns\EnsureHasToken;
use App\Support\Ploi;
use LaravelZero\Framework\Commands\Command;

class CreateServerCommand extends Command
{

    use EnsureHasToken;

    protected static $SERVER_TYPES = [
        'server',
        'load-balancer',
        'database-server',
        'redis-server'
    ];
    protected static $DATABASE_TYPES = [
        'none',
        'mysql',
        'mariadb',
        'postgresql'
    ];
    protected static $WEBSERVER_TYPES = [
        'nginx',
        'nginx-apache',
    ];
    protected $signature = 'server:create';
    protected $description = 'Creates a new server';

    public function handle(Ploi $ploi)
    {
        $this->ensureHasToken();

        $serverName = $this->ask("What should be the name of the server?", "awesome-server");

        // Provider data
        $providers = $ploi->getServerProviders();
        $sProvider = $this->menu(
            'Which provider you want to choose?',
            collect($providers)->map(fn($provider) => $provider['name'] . ' - ' . $provider['label'])->toArray()
        )->open();
        if ($sProvider === null) exit(1);
        $provider = $providers[$sProvider];
        $providerData = $providers[$sProvider]['provider'];

        // Plan data
        $plans = $providerData['plans'];
        $sPlan = $this->menu(
            "Which plan should the server be on?",
            collect($plans)->map(fn($plan) => $plan['description'])->toArray()
        )->open();
        if ($sPlan === null) exit(1);
        $plan = $plans[$sPlan];

        // Region
        $regions = $providerData['regions'];
        $sRegion = $this->menu(
            "Which location should the server be?",
            collect($regions)->map(fn($region) => "{$region['name']} ({$region['id']})")->toArray()
        )->open();
        if ($sRegion === null) exit(1);
        $region = $regions[$sRegion];

        // Server type
        $sType = $this->menu(
            "What type should the server be?",
            self::$SERVER_TYPES
        )->open();
        if ($sType === null) exit(1);
        $type = self::$SERVER_TYPES[$sType];

        // database type
        if ($type == "server" || $type == "database-server") {
            $sDatabase = $this->menu(
                "What database system you want to install?",
                self::$DATABASE_TYPES
            )->open();
            if ($sDatabase === null) exit(1);
            $database = self::$DATABASE_TYPES[$sDatabase];
        } else {
            $database = self::$DATABASE_TYPES[0];
        }

        // webserver type
        if ($type == "server") {
            $sWebserver = $this->menu(
                "What webserver system you want to install?",
                self::$WEBSERVER_TYPES
            )->open();
            if ($sWebserver === null) exit(1);
            $webserver = self::$WEBSERVER_TYPES[$sWebserver];
            $php = $this->ask("Which php version you want to install?", "7.1");
        } else {
            $webserver = self::$WEBSERVER_TYPES[0];
        }


        $this->line("Creating server...");
        $ploi->createServer(
            $serverName,
            $plan['id'],
            $region['id'],
            $provider['id'],
            $type,
            $database,
            $webserver,
            $php ?? null
        );
        $this->info("✅ Created server!");
    }

}
