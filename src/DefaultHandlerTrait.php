<?php

namespace Horde\Http\Server;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Default Handler Trait.
 *
 *
 */
trait DefaultHandlerTrait
{
    protected ResponseFactoryInterface $responseFactory;
    protected StreamFactoryInterface $streamFactory;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
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
    public function handle(RequestInterface $request): ResponseInterface
    {
        $code = method_exists($this, 'returnCode') ? $this->returnCode() : 200;
        $bodyContent = '';
        if (method_exists($this, 'bodyContent')) {
            $bodyContent = $this->bodyContent();
        }
        $body = $this->streamFactory->createStream($bodyContent);
        return $this->responseFactory->createResponse($code)->withBody($body);
    }
}
