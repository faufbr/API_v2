<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use DI\ContainerBuilder;
use Slim\Handlers\Strategies\RequestResponseArgs;

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/vendor/autoload.php';

$builder = new ContainerBuilder;

$container = $builder->addDefinitions(APP_ROOT . '/config/definitions.php')
                    ->build();

AppFactory::setContainer($container);

$app = AppFactory::create();

//Permet de transformer l'argument de l'url (ex: '/soins/2') directement en variable dans le get
$collector = $app->getRouteCollector();

$collector->setDefaultInvocationStrategy(new RequestResponseArgs);

$app->get('/soins', function (Request $request, Response $response) {

    $repository = $this->get(App\Repositories\SoinsRepository::class);

    $data = $repository->getAll();

    $body = json_encode($data);
    
    $response->getBody()->write($body);

    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/soins/{id:[0-9]+}', function (Request $request, Response $response, string $id) {

    $repository = $this->get(App\Repositories\SoinsRepository::class);

    $soin = $repository->getById((int) $id);

    if ($soin === false) {
        throw new \Slim\Exception\HttpNotFoundException($request, message: 'Soin introuvable.');
    }

    $soinJson = json_encode($soin);
    
    $response->getBody()->write($soinJson);

    return $response->withHeader('Content-type', 'application/json');
});

$app->run();