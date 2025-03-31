<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpUnauthorizedException;
use App\Repositories\TableRepository;

class VerifyTokenMiddleware
{
    private TableRepository $repository;
    public function __construct(TableRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(Request $request, RequestHandler $handler)
    {
        error_log("Middleware VerifyTokenMiddleware exécuté");

        $route = $request->getUri()->getPath();

        // Ignorer la vérification du token pour la route /login
        if ($route === '/login') {
            return $handler->handle($request);
        }
        else {
            $authorization = $request->getHeader('Authorization');

            // Vérifie si la chaîne commence par Bearer puis une suite de caractères non blancs
            if (!$authorization || !preg_match('/Bearer\s(\S+)/', $authorization[0], $matches)) {
                error_log("En-tête Authorization manquant ou mal formé");
                throw new HttpUnauthorizedException($request, 'Token invalide');
            }
            else {
                // Tableau où est stockée la partie du token après Bearer
                $jwt = $matches[1];
                
                try {
                    if (count(explode('.', $jwt)) !== 3) {
                        error_log("Token JWT mal formé");
                        throw new HttpUnauthorizedException($request, 'Token JWT mal formé');
                    }
                    else {
                        $validiteToken = $this->repository->verifyToken($jwt);

                        if ($validiteToken == false) {
                            throw new HttpUnauthorizedException($request, 'Token invalide');
                        }
                        else {
                            error_log("Token valide");
                            $request = $request->withAttribute('token', $validiteToken);
                        }
                    }
                }
                catch (\Exception $ex) {
                    error_log("Erreur lors de la vérification du token : " . $ex->getMessage());
                    throw new HttpUnauthorizedException($request, 'Token invalide : ' . $ex->getMessage());
                }
            }
            return $handler->handle($request);
        }
    }
}