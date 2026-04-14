<?php

declare(strict_types=1);

namespace Horde\Http\Server;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * HTTP Server Runner
 *
 * Run a Request through the RequestHandler (and possibly a Middleware stack)
 * and output through the ResponseWriter
 */
class Runner
{
    protected RequestHandlerInterface $handler;
    protected ResponseWriterInterface $responseWriter;

    public function __construct(
        RequestHandlerInterface $handler,
        ResponseWriterInterface $responseWriter = new ResponseWriterWeb(),
    ) {
        $this->handler = $handler;
        $this->responseWriter = $responseWriter;
    }

    public function run(ServerRequestInterface $request): void
    {
        $response = $this->handler->handle($request);
        $this->responseWriter->writeResponse($response);
    }
}
