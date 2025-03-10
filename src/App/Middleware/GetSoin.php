<?php

declare(strict_types=1);

namespace App\Middleware;


use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;
use App\Repositories\SoinsRepository;
use Slim\Exception\HttpNotFoundException;

class GetSoin
{
    public function __construct(private SoinsRepository $repository)
    {

    }
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $context = RouteContext::fromRequest($request);

        $route = $context->getRoute();

        $id = $route->getArgument('id');

        $soin = $this->repository->getById((int) $id);

        if ($soin === false) {
            throw new HttpNotFoundException($request, message: 'Soin introuvable.');
        }

        $request = $request->withAttribute('soin', $soin);

        return $handler->handle($request);
    }
}