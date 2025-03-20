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
        $routeuri = $request->getUri()->getPath();

        if ($routeuri === '/login') {
            return $handler->handle($request);
        }
        
        $tablesAutorisees = ['categorie_indisponibilite', 'categ_soins', 'convalescence', 'indisponibilite', 'infirmiere', 'lieu_convalescence', 'patient', 'personne', 'soins', 'soins_visite', 'temoignage', 'type_soins', 'visite'];

        $context = RouteContext::fromRequest($request);

        $route = $context->getRoute();

        $table = $route->getArgument('table');

        //Pour éviter injections
        if (!in_array($table, $tablesAutorisees)) {
            throw new HttpNotFoundException($request, 'Opération impossible : table non valide');
        }

        if ($request->getMethod() !== 'POST') {

            $id = $route->getArgument('id');

            $obj = $this->repository->getById((int) $id, $table);

            if ($obj === false) {
                throw new HttpNotFoundException($request, message: 'Element ' . $table . ' introuvable.');
            }
    
            $request = $request->withAttribute($table, $obj);
        }

        return $handler->handle($request);
    }
}