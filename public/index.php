<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use DI\ContainerBuilder;
use Slim\Handlers\Strategies\RequestResponseArgs;
use App\Middleware\AddJsonResponseHeader;

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

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$errorHandler = $errorMiddleware->getDefaultErrorHandler();

$errorHandler->forceContentType('application/json');

$app->add(new AddJsonResponseHeader);

$app->get('/{table}', function (Request $request, Response $response, string $table) {
    
    $repository = $this->get(App\Repositories\TableRepository::class);

    $data = $repository->getAll($table);

    $tableJson = json_encode($data);
    
    $response->getBody()->write($tableJson);

    return $response;
});

$app->get('/{table}/{id:[0-9]+}', function (Request $request, Response $response, string $table, string $id) {
    
    $obj = $request->getAttribute($table);

    $objJson = json_encode($obj);
    
    $response->getBody()->write($objJson);

    return $response;
})->add(App\Middleware\GetTable::class);

$app->run();