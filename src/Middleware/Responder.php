<?php

namespace Horde\Http\Server\Middleware;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * A Responder Middleware
 *
 * This middleware will unconditionally create a response
 *
 * Use this as a copy/paste template for starting own middleware
 */
class Responder implements MiddlewareInterface
{
    protected ResponseFactoryInterface $responseFactory;
    protected StreamFactoryInterface $streamFactory;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * Process the incoming request
     *
     * Produce an own response object, do not delegate to handler
     *
     * @param ServerRequestInterface $request The request
     * @param RequestHandlerInterface $handler The controlling request handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $body = $this->streamFactory->createStream('ResponderMiddleware');
        return $this->responseFactory->createResponse(200)->withBody($body);
    }
}
