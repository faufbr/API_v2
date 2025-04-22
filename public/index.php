<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use DI\ContainerBuilder;
use Slim\Handlers\Strategies\RequestResponseArgs;
use App\Middleware\AddJsonResponseHeader;
use App\Repositories\TableRepository;
use App\Middleware\VerifyTokenMiddleware;

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/vendor/autoload.php';

$builder = new ContainerBuilder;

$container = $builder->addDefinitions(APP_ROOT . '/config/definitions.php')
                    ->build();

AppFactory::setContainer($container);

// Slim utilise le conteneur d'injection de dépendances (DI) pour instancier la classe
$container->set(VerifyTokenMiddleware::class, function ($container) {
    // Récupère TableRepository depuis le conteneur
    $repository = $container->get(TableRepository::class);
    return new VerifyTokenMiddleware($repository);
});

$app = AppFactory::create();

//Permet de transformer l'argument de l'url (ex: '/soins/2') directement en variable dans le get
$collector = $app->getRouteCollector();

$collector->setDefaultInvocationStrategy(new RequestResponseArgs);

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$errorHandler = $errorMiddleware->getDefaultErrorHandler();

$errorHandler->forceContentType('application/json');

$app->add(new AddJsonResponseHeader);

require __DIR__ . '/../src/App/Routes.php';

$app->run();