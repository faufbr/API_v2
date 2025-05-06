<?php

declare(strict_types=1);

namespace Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Response;
use App\Middleware\VerifyTokenMiddleware;
use App\Repositories\TableRepository;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;

class VerifyTokenMiddlewareTest extends TestCase
{
    private TableRepository $repositoryMock;
    private VerifyTokenMiddleware $middleware;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(TableRepository::class);
        $this->middleware = new VerifyTokenMiddleware($this->repositoryMock);
    }

    public function testMiddlewarePermetRouteLogin(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/login');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new Response());

        $response = $this->middleware->__invoke($request, $handler);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testMiddlewareErreurAuthorizationHeaderManquant(): void
    {
        $this->expectException(HttpUnauthorizedException::class);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/protected');
        $handler = $this->createMock(RequestHandlerInterface::class);

        $this->middleware->__invoke($request, $handler);
    }

    public function testMiddlewareTokenValide(): void
    {
        $validToken = 'valid.jwt.token';

        // Retourne token valide
        $this->repositoryMock->expects($this->once())
            ->method('verifyToken')
            ->with($validToken)
            ->willReturn(['id' => 1, 'function' => 'patient']);

        // Retourne user valide
        $this->repositoryMock->expects($this->once())
            ->method('getUserById')
            ->with(1)
            ->willReturn(['id' => 1, 'name' => 'Test User']);

        // Retourne rôle valide
        $this->repositoryMock->expects($this->once())
            ->method('getRole')
            ->with(1)
            ->willReturn('patient');

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/badge')
            ->withHeader('Authorization', 'Bearer ' . $validToken);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new Response());

        $response = $this->middleware->__invoke($request, $handler);

        $this->assertInstanceOf(Response::class, $response);
    }
}