<?php

namespace Horde\Http\Server;

use Horde\Http\ResponseFactory;
use Horde\Http\StreamFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

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
 * The final response returned by RampageRequestHandler should
 *
 * Note that middlewares or the payload could themselves delegate their
 * duties to other middlewares, handlers or other code
 */
class RampageRequestHandler implements RequestHandlerInterface
{
    protected ResponseFactoryInterface $responseFactory;
    protected StreamFactoryInterface $streamFactory;
    /**
     * @var MiddlewareInterface[]
     */
    private array $middlewares = [];

    private ?RequestHandlerInterface $payloadHandler;

    /**
     * Constructor
     *
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface $streamFactory
     * @param MiddlewareInterface[] $middlewares
     * @param RequestHandlerInterface|null $payloadHandler
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory = new ResponseFactory(),
        StreamFactoryInterface $streamFactory = new StreamFactory(),
        iterable $middlewares = [],
        ?RequestHandlerInterface $payloadHandler = null,
    ) {
        // Needed for the fallback response in case of no payload
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        // We accept any iterable but cast it to array
        $this->middlewares = (array) $middlewares;
        $this->payloadHandler = $payloadHandler;
    }

    /**
     * Add another middleware to the queue just before the payload handler
     */
    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Configure the payload handler
     */
    public function setPayloadHandler(RequestHandlerInterface $handler): void
    {
        $this->payloadHandler = $handler;
    }

    public function nextMiddleware(): ?MiddlewareInterface
    {
        return array_shift($this->middlewares);
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
     * Finally the we will return a response ourselves.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->nextMiddleware();
        if ($middleware) {
            return $middleware->process($request, $this);
        }
        if ($this->payloadHandler) {
            return $this->payloadHandler->handle($request);
        }
        // Fallback response
        $code = 404;
        $reason = 'No Response by middleware or payload Handler';
        $body = $this->streamFactory->createStream($reason);

        return $this->responseFactory->createResponse($code, $reason)->withBody($body);
    }
}
