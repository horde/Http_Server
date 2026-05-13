<?php

declare(strict_types=1);

namespace Horde\Http\Server\Test\Unit\Middleware;

use Horde\Http\ResponseFactory;
use Horde\Http\StreamFactory;
use Horde\Http\Server\Middleware\Gzip;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(Gzip::class)]
class GzipTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $gzip = new Gzip();
        $this->assertInstanceOf(Gzip::class, $gzip);
    }

    public function testConstructWithExplicitFactory(): void
    {
        $gzip = new Gzip(new StreamFactory());
        $this->assertInstanceOf(Gzip::class, $gzip);
    }

    public function testProcessSetsGzipHeader(): void
    {
        $gzip = new Gzip();
        $responseFactory = new ResponseFactory();
        $streamFactory = new StreamFactory();

        $body = $streamFactory->createStream('Hello World');
        $body->rewind();
        $innerResponse = $responseFactory->createResponse(200)->withBody($body);

        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($innerResponse);

        $response = $gzip->process($request, $handler);

        $this->assertSame('gzip', $response->getHeaderLine('Content-Encoding'));
    }

    public function testProcessReadsBodyContent(): void
    {
        $gzip = new Gzip();
        $responseFactory = new ResponseFactory();
        $streamFactory = new StreamFactory();

        $body = $streamFactory->createStream('Test content');
        $body->rewind();
        $innerResponse = $responseFactory->createResponse(200)->withBody($body);

        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($innerResponse);

        $response = $gzip->process($request, $handler);

        $this->assertSame('Test content', (string) $response->getBody());
    }

    public function testProcessPreservesStatusCode(): void
    {
        $gzip = new Gzip();
        $responseFactory = new ResponseFactory();
        $streamFactory = new StreamFactory();

        $body = $streamFactory->createStream('data');
        $body->rewind();
        $innerResponse = $responseFactory->createResponse(201)->withBody($body);

        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($innerResponse);

        $response = $gzip->process($request, $handler);

        $this->assertSame(201, $response->getStatusCode());
    }
}
