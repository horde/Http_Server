<?php

declare(strict_types=1);

namespace Horde\Http\Server\Test\Unit\Middleware;

use Horde\Http\ResponseFactory;
use Horde\Http\Server\Middleware\Mock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(Mock::class)]
class MockTest extends TestCase
{
    public function testProcessReturnsPrefabricatedResponse(): void
    {
        $responseFactory = new ResponseFactory();
        $expected = $responseFactory->createResponse(418);
        $mock = new Mock($expected);

        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);

        $response = $mock->process($request, $handler);

        $this->assertSame($expected, $response);
        $this->assertSame(418, $response->getStatusCode());
    }
}
