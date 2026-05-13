<?php

declare(strict_types=1);

namespace Horde\Http\Server\Test\Unit\Middleware;

use Horde\Http\ResponseFactory;
use Horde\Http\StreamFactory;
use Horde\Http\Server\Middleware\JsonBodyParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(JsonBodyParser::class)]
class JsonBodyParserTest extends TestCase
{
    private StreamFactory $streamFactory;
    private ResponseInterface $dummyResponse;

    protected function setUp(): void
    {
        $this->streamFactory = new StreamFactory();
        $this->dummyResponse = (new ResponseFactory())->createResponse(200);
    }

    public function testParsesJsonBody(): void
    {
        $parser = new JsonBodyParser();
        $body = $this->streamFactory->createStream('{"name":"test","value":42}');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->atLeastOnce())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');
        $request->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn($body);
        $request->expects($this->once())
            ->method('withParsedBody')
            ->with(['name' => 'test', 'value' => 42])
            ->willReturn($request);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->dummyResponse);

        $parser->process($request, $handler);
    }

    public function testSkipsNonJsonContentType(): void
    {
        $parser = new JsonBodyParser();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->atLeastOnce())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('text/html');
        $request->expects($this->never())->method('withParsedBody');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->dummyResponse);

        $parser->process($request, $handler);
    }

    public function testSkipsInvalidJson(): void
    {
        $parser = new JsonBodyParser();
        $body = $this->streamFactory->createStream('{invalid json}');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->atLeastOnce())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');
        $request->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn($body);
        $request->expects($this->never())->method('withParsedBody');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->dummyResponse);

        $parser->process($request, $handler);
    }

    public function testSkipsEmptyBody(): void
    {
        $parser = new JsonBodyParser();
        $body = $this->streamFactory->createStream('');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->atLeastOnce())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');
        $request->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn($body);
        $request->expects($this->never())->method('withParsedBody');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->dummyResponse);

        $parser->process($request, $handler);
    }

    public function testSkipsJsonString(): void
    {
        $parser = new JsonBodyParser();
        $body = $this->streamFactory->createStream('"just a string"');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->atLeastOnce())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');
        $request->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn($body);
        $request->expects($this->never())->method('withParsedBody');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->dummyResponse);

        $parser->process($request, $handler);
    }

    public function testSkipsJsonInteger(): void
    {
        $parser = new JsonBodyParser();
        $body = $this->streamFactory->createStream('42');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->atLeastOnce())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');
        $request->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn($body);
        $request->expects($this->never())->method('withParsedBody');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->dummyResponse);

        $parser->process($request, $handler);
    }
}
