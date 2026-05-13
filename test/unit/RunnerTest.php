<?php

declare(strict_types=1);

namespace Horde\Http\Server\Test\Unit;

use Horde\Http\ResponseFactory;
use Horde\Http\Server\ResponseWriterInterface;
use Horde\Http\Server\ResponseWriterWeb;
use Horde\Http\Server\Runner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(Runner::class)]
class RunnerTest extends TestCase
{
    public function testConstructWithDefaultResponseWriter(): void
    {
        $handler = $this->createStub(RequestHandlerInterface::class);
        $runner = new Runner($handler);
        $this->assertInstanceOf(Runner::class, $runner);
    }

    public function testConstructWithExplicitResponseWriter(): void
    {
        $handler = $this->createStub(RequestHandlerInterface::class);
        $writer = $this->createStub(ResponseWriterInterface::class);
        $runner = new Runner($handler, $writer);
        $this->assertInstanceOf(Runner::class, $runner);
    }

    public function testRunDelegatesToHandlerAndWriter(): void
    {
        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse(200);

        $request = $this->createStub(ServerRequestInterface::class);

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $writer = $this->createMock(ResponseWriterInterface::class);
        $writer->expects($this->once())
            ->method('writeResponse')
            ->with($response);

        $runner = new Runner($handler, $writer);
        $runner->run($request);
    }
}
