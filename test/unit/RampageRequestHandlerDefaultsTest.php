<?php

declare(strict_types=1);

namespace Horde\Http\Server\Test\Unit;

use Horde\Http\RequestFactory;
use Horde\Http\ResponseFactory;
use Horde\Http\StreamFactory;
use Horde\Http\Server\RampageRequestHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Tests for constructor default parameter support in RampageRequestHandler
 */
#[CoversClass(RampageRequestHandler::class)]
class RampageRequestHandlerDefaultsTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $handler = new RampageRequestHandler();
        $this->assertInstanceOf(RampageRequestHandler::class, $handler);
    }

    public function testConstructWithDefaultsHandlesRequest(): void
    {
        $handler = new RampageRequestHandler();
        $requestFactory = new RequestFactory();
        $request = $requestFactory->createServerRequest('GET', 'https://example.org');

        $response = $handler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testConstructWithMiddlewaresIterable(): void
    {
        $responseFactory = new ResponseFactory();
        $middleware = $this->createStub(MiddlewareInterface::class);
        $middleware->method('process')->willReturn($responseFactory->createResponse(201));

        $handler = new RampageRequestHandler(
            middlewares: [$middleware],
        );

        $requestFactory = new RequestFactory();
        $request = $requestFactory->createServerRequest('GET', 'https://example.org');
        $response = $handler->handle($request);

        $this->assertSame(201, $response->getStatusCode());
    }
}
