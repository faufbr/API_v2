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
    
    //Récupère le repository via le container DI
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

$app->put('/{table}/{id:[0-9]+}', function (Request $request, Response $response, string $table, string $id) {
    
    $params = $request->getParsedBody();

    $repository = $this->get(App\Repositories\TableRepository::class);

    if (empty($params)) {
        $msgErreur = json_encode(["erreur" => "0 paramètre fourni"]);
        $response->getBody()->write($msgErreur);
        $vreponse = $response->withStatus(400);
    }
    else {
        try {
            $reussi = $repository->update((int)$id, $params, $table);
    
            if (!$reussi) {
                $msgErreur = json_encode(["erreur" => "Mise à jour impossible"]);
                $response->getBody()->write($msgErreur);
                $vreponse = $response->withStatus(404);
            }
            else {
                $updatedObj = $repository->getById((int)$id, $table);
                $response->getBody()->write(json_encode($updatedObj));
                $vreponse = $response;
            }
        }
        catch (PDOException $ex) {
            $msgErreur = json_encode(["erreur" => "Erreur BDD : " . $ex->getMessage()]);
            $response->getBody()->write($msgErreur);
            $vreponse = $response->withStatus(500);
        }  
    }
    
    return $vreponse;

})->add(App\Middleware\GetTable::class);

$app->delete('/{table}/{id:[0-9]+}', function (Request $request, Response $response, string $table, string $id) {
    
    $repository = $this->get(App\Repositories\TableRepository::class);

    try {
        $reussi = $repository->delete((int)$id, $table);

        if(!$reussi) {
            $msgErreur = json_encode(["erreur" => "Suppression impossible"]);
            $response->getBody()->write($msgErreur);
            $vreponse = $response->withStatus(404);
        }
        else {
            $vreponse = $response->withStatus(204);
        }
    }
    catch(PDOException $ex) {
        $msgErreur = json_encode(["erreur" => "Erreur BDD : " . $ex->getMessage()]);
        $response->getBody()->write($msgErreur);
        $vreponse = $response->withStatus(500);
    }

    return $vreponse;

})->add(App\Middleware\GetTable::class);

//Parse les requêtes http en données sous forme de tableau accessible ensuite via getParsedBody()
$app->addBodyParsingMiddleware();

$app->run();