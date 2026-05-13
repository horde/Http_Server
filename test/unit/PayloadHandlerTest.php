<?php

declare(strict_types=1);

namespace Horde\Http\Server\Test\Unit;

use Horde\Http\RequestFactory;
use Horde\Http\ResponseFactory;
use Horde\Http\StreamFactory;
use Horde\Http\Server\PayloadHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(PayloadHandler::class)]
class PayloadHandlerTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $handler = new PayloadHandler();
        $this->assertInstanceOf(PayloadHandler::class, $handler);
    }

    public function testConstructWithExplicitFactories(): void
    {
        $handler = new PayloadHandler(new ResponseFactory(), new StreamFactory());
        $this->assertInstanceOf(PayloadHandler::class, $handler);
    }

    public function testHandleReturns200WithPayloadBody(): void
    {
        $handler = new PayloadHandler();
        $requestFactory = new RequestFactory();
        $request = $requestFactory->createServerRequest('GET', 'https://example.org');

        $response = $handler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Payload', (string) $response->getBody());
    }
}
