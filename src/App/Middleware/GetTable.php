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
    
    //Permet de gérer les erreurs http
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $context = RouteContext::fromRequest($request);

        $route = $context->getRoute();

        $table = $route->getArgument('table');
        $id = $route->getArgument('id');

        $obj = $this->repository->getById((int) $id, $table);

        if ($obj === false) {
            throw new HttpNotFoundException($request, message: 'Objet ' . $table . ' introuvable.');
        }

        $request = $request->withAttribute($table, $obj);

        return $handler->handle($request);
    }
}