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

        $token = $request->getAttribute('token');
        $role = $token['function'] ?? 'patient';

        $acces = [
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
        ];

        // Attribution de l'accès aux tables en fonction du rôle. S'il y a un rôle qui n'est pas défini dans ACCES, on ne lui donne aucune table à accéder
        $tablesAutorisees = $acces[$role] ?? [];

        $context = RouteContext::fromRequest($request);

        $route = $context->getRoute();

        $table = $route->getArgument('table');

        // Pour éviter les accès non autorisés
        if (!in_array($table, $tablesAutorisees)) {
            throw new HttpNotFoundException($request, 'Opération impossible : table non valide');
        }

        if ($request->getMethod() !== 'POST') {

            $id = $route->getArgument('id');

            if ($id != null)
            {
                $obj = $this->repository->getById((int) $id, $table);
                if ($obj === false) {
                    throw new HttpNotFoundException($request, message: 'Element ' . $table . ' introuvable.');
                }
        
                $request = $request->withAttribute($table, $obj);
            }
        }

        return $handler->handle($request);
    }
}