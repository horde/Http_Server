<?php

declare(strict_types=1);

namespace Horde\Http\Server\Test\Unit;

use Horde\Http\RequestFactory;
use Horde\Http\ResponseFactory;
use Horde\Http\StreamFactory;
use Horde\Http\Server\DefaultHandlerTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Minimal handler using DefaultHandlerTrait without overriding
 * bodyContent() or returnCode(), so trait fallbacks are exercised.
 */
class BareHandler implements RequestHandlerInterface
{
    use DefaultHandlerTrait;
}

#[CoversClass(BareHandler::class)]
class DefaultHandlerTraitTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $handler = new BareHandler();
        $this->assertInstanceOf(BareHandler::class, $handler);
    }

    public function testConstructWithExplicitFactories(): void
    {
        $handler = new BareHandler(new ResponseFactory(), new StreamFactory());
        $this->assertInstanceOf(BareHandler::class, $handler);
    }

    public function testHandleFallbackReturns200WithEmptyBody(): void
    {
        $handler = new BareHandler();
        $requestFactory = new RequestFactory();
        $request = $requestFactory->createServerRequest('GET', 'https://example.org');

        $response = $handler->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
    }
}
