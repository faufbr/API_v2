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
        $route = $request->getUri()->getPath();

        // Ignorer la vérification du token pour la route /login
        if ($route === '/login') {
            return $handler->handle($request);
        }
        else {
            $authorization = $request->getHeader('Authorization');
            error_log("Authorization Header : " . print_r($authorization, true));

            // Vérifie si la chaîne commence par Bearer puis une suite de caractères non blancs
            if (!$authorization || !preg_match('/Bearer\s(\S+)/', $authorization[0], $matches)) {
                throw new HttpUnauthorizedException($request, 'Token invalide');
            }
            else {
                // Tableau où est stockée la partie du token après Bearer
                $jwt = $matches[1];
                
                try {
                    if (count(explode('.', $jwt)) !== 3) {
                        throw new HttpUnauthorizedException($request, 'Token JWT mal formé');
                    }
                    else {
                        $validiteToken = $this->repository->verifyToken($jwt);
                        error_log("JWT extrait : " . $jwt);
                        error_log("Validité du token : " . print_r($validiteToken, true));

                        if ($validiteToken == false) {
                            error_log("Token absent ou expiré !");
                            throw new HttpUnauthorizedException($request, 'Token invalide');
                        }
                        else {

                            $userId = $validiteToken['id'] ?? null;
                            error_log("user id : " . $userId);
                            if (!$userId) {
                                throw new HttpUnauthorizedException($request, 'ID utilisateur manquant dans le token');
                            }

                            // Récupère les infos user
                            $user = $this->repository->getUserById($userId);
                            if (!$user) {
                                throw new HttpUnauthorizedException($request, 'Utilisateur introuvable');
                            }
                            $role = $this->repository->getRole($userId);
                            error_log($role);

                            $rolesAutorises = ['infirmiere', 'infirmiere_cheffe', 'patient', 'administrateur'];

                            if (!in_array($role, $rolesAutorises)) {
                                throw new HttpUnauthorizedException($request, 'Accès refusé');
                            }
                            else
                            {
                                error_log("Token validé, rôle injecté : " . $role);
                                $request = $request->withAttribute('token', $validiteToken)
                                ->withAttribute('user_id', $validiteToken['id'])
                                ->withAttribute('user_role', $role);
                                error_log("Attributs injectés : user_id = " . $validiteToken['id'] . ", user_role = " . $role);
                            }
                        }
                    }
                }
                catch (\Exception $ex) {
                    error_log("Erreur lors de la vérification du token : " . $ex->getMessage());
                    throw new HttpUnauthorizedException($request, 'Token invalide : ' . $ex->getMessage());
                }
            }
            error_log("Token injecté dans la requête : " . print_r($validiteToken, true));

            return $handler->handle($request);
        }
    }
}