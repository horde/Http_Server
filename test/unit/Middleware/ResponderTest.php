<?php

declare(strict_types=1);

namespace Horde\Http\Server\Test\Unit\Middleware;

use Horde\Http\ResponseFactory;
use Horde\Http\StreamFactory;
use Horde\Http\Server\Middleware\Responder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(Responder::class)]
class ResponderTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $responder = new Responder();
        $this->assertInstanceOf(Responder::class, $responder);
    }

    public function testConstructWithExplicitFactories(): void
    {
        $responder = new Responder(new ResponseFactory(), new StreamFactory());
        $this->assertInstanceOf(Responder::class, $responder);
    }

    public function testProcessReturns200(): void
    {
        $responder = new Responder();
        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);

        $response = $responder->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ResponderMiddleware', (string) $response->getBody());
    }
}
