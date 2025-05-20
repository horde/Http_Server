<?php

namespace Horde\Http\Server;

use Psr\Http\Message\ResponseInterface;

interface ResponseWriterInterface
{
    /**
     * Output the HTTP Response to STDOUT
     */
    public function writeResponse(ResponseInterface $response): void;
}
