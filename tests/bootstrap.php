<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

$kernel = new \App\Kernel('test', true);

$kernel->boot();

$application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
$application->setAutoExit(false);

passthru(\sprintf('APP_ENV=%s php "%s/../bin/console" doctrine:database:drop --force --if-exists', $_ENV['APP_ENV'], __DIR__));
passthru(\sprintf('APP_ENV=%s php "%s/../bin/console" doctrine:database:create', $_ENV['APP_ENV'], __DIR__));
passthru(\sprintf('APP_ENV=%s php "%s/../bin/console" doctrine:migrations:migrate --no-interaction', $_ENV['APP_ENV'], __DIR__));


$kernel->shutdown();
