<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Middleware\GetTable;
use App\Middleware\VerifyTokenMiddleware;

$app->get('/{table}', function (Request $request, Response $response, string $table) {
    
    //Récupère le repository via le container DI
    $repository = $this->get(App\Repositories\TableRepository::class);

    $data = $repository->getAll($table);

    $tableJson = json_encode($data);
    
    $response->getBody()->write($tableJson);

    return $response;
    
})->add(App\Middleware\VerifyTokenMiddleware::class)
->add(App\Middleware\GetTable::class);

$app->get('/{table}/{id:[0-9]+}', function (Request $request, Response $response, string $table, string $id) {
    
    $obj = $request->getAttribute($table);

    $objJson = json_encode($obj);
    
    $response->getBody()->write($objJson);

    return $response;

})->add(App\Middleware\VerifyTokenMiddleware::class)
->add(App\Middleware\GetTable::class);

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

})->add(App\Middleware\VerifyTokenMiddleware::class)
->add(App\Middleware\GetTable::class);

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

})->add(App\Middleware\VerifyTokenMiddleware::class)
->add(App\Middleware\GetTable::class);

$app->post('/login', function (Request $request, Response $response) {
    
    $params = $request->getParsedBody();

    if (empty($params)) {
        $msgErreur = json_encode(["erreur" => "0 paramètre fourni"]);
        $response->getBody()->write($msgErreur);
        $vreponse = $response->withStatus(400);
    }
    else {
        $repository = $this->get(App\Repositories\TableRepository::class);

        try {
            $user = $repository->login($params);
            
            if ($user === false) {
                $msgErreur = json_encode(["erreur" => "Login ou mot de passe incorrect"]);
                $response->getBody()->write($msgErreur);
                $vreponse = $response->withStatus(401);
            }
            else {
                $response->getBody()->write(json_encode($user));
                $vreponse = $response->withStatus(200);
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

$app->post('/{table}', function (Request $request, Response $response, string $table) {
    
    $params = $request->getParsedBody();

    if (empty($params)) {
        $msgErreur = json_encode(["erreur" => "0 paramètre fourni"]);
        $response->getBody()->write($msgErreur);
        $vreponse = $response->withStatus(400);
    }
    else {
        try {
            $repository = $this->get(App\Repositories\TableRepository::class);
            $nouvelId = $repository->create($params, $table);
    
            if ($nouvelId === 0) {
                $msgErreur = json_encode(["erreur" => "Création impossible"]);
                $response->getBody()->write($msgErreur);
                $vreponse = $response->withStatus(500);
            }
            else {
                $objCree = $repository->getById($nouvelId, $table);
                $response->getBody()->write(json_encode($objCree));
                $vreponse = $response->withStatus(201);
            }
        }
        catch (PDOException $ex) {
            $msgErreur = json_encode(["erreur" => "Erreur BDD : " . $ex->getMessage()]);
            $response->getBody()->write($msgErreur);
            $vreponse = $response->withStatus(500);
        }  
    }
    
    return $vreponse;

})->add(App\Middleware\VerifyTokenMiddleware::class)
->add(App\Middleware\GetTable::class);

//Parse les requêtes http en données sous forme de tableau accessible ensuite via getParsedBody()
$app->addBodyParsingMiddleware();