<?php

namespace Horde\Http\Server\Middleware;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A Mock Middleware
 *
 * This middleware will unconditionally return a prefabricated a response
 *
 * Returns a preset response
 */
class Mock implements MiddlewareInterface
{
    private ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
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
        return $this->response;
    }
}
