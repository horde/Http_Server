<?php

namespace Horde\Http\Server;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * A very simple payload handler.
 *
 * Derive classes from this handler only for very basic use cases.
 *
 * Otherwise, override at least handle() and implement interaction
 * with the body stream and headers as needed.
 *
 */
class PayloadHandler implements RequestHandlerInterface
{
    use DefaultHandlerTrait;

    /**
     * Overload this function to return different body content.
     */
    protected function bodyContent(): string
    {
        return 'Payload';
    }

    /**
     * Overload this function to change the return code
     */
    protected function returnCode(): int
    {
        return 200;
    }
}
