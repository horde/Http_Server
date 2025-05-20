<?php

namespace Horde\Http\Server\Test\Unit;

use PHPUnit\Framework\TestCase;
use Horde\Http\RequestFactory;
use Horde\Http\ResponseFactory;
use Horde\Http\StreamFactory;
use Horde\Http\Server\RampageRequestHandler;
use Horde\Http\ServerRequest;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
class RampageRequestHandlerTest extends TestCase
{
    public function testAddMiddleware()
    {
        $responseFactory = new ResponseFactory();
        $streamFactory = new StreamFactory();
        $mockRequest = $this->createMock(ServerRequest::class);
        $handler = new RampageRequestHandler($responseFactory, $streamFactory);
        $middlewareMock1 = $this->createMock(MiddlewareInterface::class);
        $middlewareMock1->method('process')->willReturn($responseFactory->createResponse(201));
        $middlewareMock2 = $this->createMock(MiddlewareInterface::class);
        $middlewareMock2->method('process')->willReturn($responseFactory->createResponse(202));
        $middlewareMock3 = $this->createMock(MiddlewareInterface::class);
        $middlewareMock3->method('process')->willReturn($responseFactory->createResponse(203));
        $handler->addMiddleware($middlewareMock1);
        $handler->addMiddleware($middlewareMock2);
        $handler->addMiddleware($middlewareMock3);
        $firstInNumber = $handler->nextMiddleware()->process($mockRequest, $handler)->getStatusCode();
        $this->assertSame(201, $firstInNumber);
        $firstInNumber = $handler->nextMiddleware()->process($mockRequest, $handler)->getStatusCode();
        $this->assertSame(202, $firstInNumber);
        $firstInNumber = $handler->nextMiddleware()->process($mockRequest, $handler)->getStatusCode();
        $this->assertSame(203, $firstInNumber);
        $emptyStak = $handler->nextMiddleware();
        $this->assertSame(null, $emptyStak);
    }

    public function testSetPayloadHandler()
    {
        $responseFactory = new ResponseFactory();
        $streamFactory = new StreamFactory();
        $handler = new RampageRequestHandler($responseFactory, $streamFactory);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);
        $requestHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $handler->setPayloadHandler($requestHandlerMock);
        $requestHandlerMock->method('handle')->willReturn($responseFactory->createResponse());
        $handled = $handler->handle($serverRequestMock);
        $this->assertSame(200, $handled->getStatusCode());
    }

    public function testHandleWithSetMiddelware()
    {
        $responseFactory = new ResponseFactory();
        $streamFactory = new StreamFactory();
        $handler = new RampageRequestHandler($responseFactory, $streamFactory);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);
        $middlewareMock = $this->createMock(MiddlewareInterface::class);
        $middlewareMock->method('process')->willReturn($responseFactory->createResponse(418));
        $handler->addMiddleware($middlewareMock);
        $handled = $handler->handle($serverRequestMock);
        $this->assertSame(418, $handled->getStatusCode());
        $handled = $handler->handle($serverRequestMock);
        $this->assertSame(404, $handled->getStatusCode());
    }

    public function testHandle()
    {
        $responseFactory = new ResponseFactory();
        $streamFactory = new StreamFactory();
        $requestFactory = new RequestFactory();
        $handler = new RampageRequestHandler($responseFactory, $streamFactory);
        $handled = $handler->handle($requestFactory->createServerRequest('GET', 'www.test.de'));
        $this->assertSame(404, $handled->getStatusCode());
        $this->assertSame('No Response by middleware or payload Handler', $handled->getReasonPhrase());
    }
}
