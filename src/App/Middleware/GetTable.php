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
        error_log("Middleware GetTable exécuté");

        $routeuri = $request->getUri()->getPath();

        if ($routeuri === '/login') {
            return $handler->handle($request);
        }
        
        $tablesAutorisees = ['chambre_forte', 'badge', 'categorie_indisponibilite', 'categ_soins', 'convalescence', 'indisponibilite', 'infirmiere', 'lieu_convalescence', 'patient', 'personne', 'soins', 'soins_visite', 'temoignage', 'type_soins', 'visite'];

        define('ACCES', [
            'administrateur' => [
                'administateur',
                'badge',
                'categorie_indisponibilite',
                'categ_soins',
                'chambre_forte',
                'convalescence',
                'indisponibilite',
                'infirmiere',
                'infirmiere_badge',
                'lieu_convalescence',
                'patient',
                'personne',
                'personne_login',
                'soins',
                'soins_visite',
                'temoignage',
                'type_soins',
                'visite',
            ],
            'infirmiere' => [
                'soins',
                'soins_visite',
                'categ_soins',
                'patient',
                'visite'
            ],
            'infirmiere_cheffe' => [
                'soins',
                'soins_visite',
                'categ_soins',
                'patient',
                'visite',
                'infirmiere',
                'personne',
                'badge',
                'convalescence',
                'type_soins'
            ],
            'patient' => [
                'infirmiere',
                'visite'
            ]
        ]);

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
        error_log("Middleware GetTable terminé");

        return $handler->handle($request);
    }
}