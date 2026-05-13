<?php

declare(strict_types=1);

namespace Horde\Http\Server;

use Horde\Http\ResponseFactory;
use Horde\Http\StreamFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

/**
 * The Rampage request handler.
 *
 * Enqueue any list of middleware and optionally a payload request handler.
 *
 * Each middleware a request passes may change the request details,
 * add middleware to the bottom of the queue or cause side effects.
 *
 * Calculated, loaded or derived data may be stored in a request attribute.
 *
 * The list of middleware will be processed until a response is returned or
 * the list has been consumed. In this case, a payload request handler will
 * produce the initial Response object. If there is no payload request handler,
 * the RampageRequestHandler itself will create a very simple response.
 *
 * Once a response is returned, the response object passes back through
 * all the previous layers and may be changed by them or cause side effects.
 *
 * Note that middlewares or the payload could themselves delegate their
 * duties to other middlewares, handlers or other code
 */
class RampageRequestHandler implements RequestHandlerInterface
{
    protected ResponseFactoryInterface $responseFactory;
    protected StreamFactoryInterface $streamFactory;
    private ?ContainerInterface $container;
    /** @var array<string|MiddlewareInterface> */
    private array $middlewares = [];
    private string|RequestHandlerInterface|null $payloadHandler;

    /**
     * Constructor
     *
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface $streamFactory
     * @param iterable<string|MiddlewareInterface> $middlewares
     * @param string|RequestHandlerInterface|null $payloadHandler
     * @param ContainerInterface|null $container PSR-11 container for lazy middleware resolution
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory = new ResponseFactory(),
        StreamFactoryInterface $streamFactory = new StreamFactory(),
        iterable $middlewares = [],
        string|RequestHandlerInterface|null $payloadHandler = null,
        ?ContainerInterface $container = null,
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->middlewares = [...$middlewares];
        $this->payloadHandler = $payloadHandler;
        $this->container = $container;
    }

    /**
     * Add another middleware to the queue just before the payload handler
     */
    public function addMiddleware(string|MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Configure the payload handler
     */
    public function setPayloadHandler(string|RequestHandlerInterface $handler): void
    {
        $this->payloadHandler = $handler;
    }

    public function nextMiddleware(): ?MiddlewareInterface
    {
        $entry = array_shift($this->middlewares);
        if ($entry === null) {
            return null;
        }
        if ($entry instanceof MiddlewareInterface) {
            return $entry;
        }
        if ($this->container === null) {
            throw new RuntimeException(
                sprintf('Cannot resolve middleware "%s": no container provided.', $entry)
            );
        }
        $resolved = $this->container->get($entry);
        if (!$resolved instanceof MiddlewareInterface) {
            throw new RuntimeException(
                sprintf('Container returned non-middleware for "%s": got %s', $entry, get_debug_type($resolved))
            );
        }
        return $resolved;
    }

    /**
     * Handle a request
     *
     * Each middleware will either create a response or
     * return control to the handler.
     *
     * If the middlewares created no response,
     * the payload handler will.
     *
     * Finally we will return a response ourselves.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->nextMiddleware();
        if ($middleware) {
            return $middleware->process($request, $this);
        }
        $payloadHandler = $this->resolvePayloadHandler();
        if ($payloadHandler) {
            return $payloadHandler->handle($request);
        }
        // Fallback response
        $code = 404;
        $reason = 'No Response by middleware or payload Handler';
        $body = $this->streamFactory->createStream($reason);

        return $this->responseFactory->createResponse($code, $reason)->withBody($body);
    }

    private function resolvePayloadHandler(): ?RequestHandlerInterface
    {
        if ($this->payloadHandler === null) {
            return null;
        }
        if ($this->payloadHandler instanceof RequestHandlerInterface) {
            return $this->payloadHandler;
        }
        if ($this->container === null) {
            throw new RuntimeException(
                sprintf('Cannot resolve payload handler "%s": no container provided.', $this->payloadHandler)
            );
        }
        $resolved = $this->container->get($this->payloadHandler);
        if (!$resolved instanceof RequestHandlerInterface) {
            throw new RuntimeException(
                sprintf('Container returned non-handler for "%s": got %s', $this->payloadHandler, get_debug_type($resolved))
            );
        }
        $this->payloadHandler = $resolved;
        return $resolved;
    }
}
