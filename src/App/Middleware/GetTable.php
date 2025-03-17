<?php

declare(strict_types=1);

namespace App\Middleware;


use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;
use App\Repositories\TableRepository;
use Slim\Exception\HttpNotFoundException;

class GetTable
{
    public function __construct(private TableRepository $repository)
    {
    }
    
    //Valide l'existence de la table demandée, la récupère et gère les erreurs 404 liées à cette demande
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $tablesNonAutorisees = ['administrateur'];

        $context = RouteContext::fromRequest($request);

        $route = $context->getRoute();

        $table = $route->getArgument('table');
        $id = $route->getArgument('id');

        //Pour éviter injections sql
        if (in_array($table, $tablesNonAutorisees)) {
            throw new HttpNotFoundException($request, 'Opération impossible : table non valide');
        }

        $obj = $this->repository->getById((int) $id, $table);

        if ($obj === false) {
            throw new HttpNotFoundException($request, message: 'Objet ' . $table . ' introuvable.');
        }

        $request = $request->withAttribute($table, $obj);

        return $handler->handle($request);
    }
}