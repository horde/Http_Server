<?php

/**
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   James Pepin <james@bluestatedigital.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Controller
 */

namespace Horde\Http\Server;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Filter to gzip content before being served
 *
 * @author    James Pepin <james@bluestatedigital.com>
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2008-2021 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Horde\Http\Server
 */
class Gzip implements MiddlewareInterface
{
    private StreamFactoryInterface $streamFactory;

    public function __construct(StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    /**
     * Compress output stream and set header
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // First let the request pass unchanged
        $response = $handler->handle($request);

        // Compress the response and set header
        $stream = $response->getBody();
        // TODO: Consume stream in chunks, this could be large amounts of data.
        $compressedStream = $this->streamFactory->createStream($stream->getContents());
        $response = $response->withHeader('Content-Encoding', 'gzip');
        // TODO: Make this work again
        //        $response = $response->withHeader('Content-Length', $this->_byteCount($body));
        $response = $response->withBody($compressedStream);

        return $response;
    }

    /**
     * If mbstring is set to overload str* function then we could be counting
     * multi-byte chars as single bytes so we need to treat the string like its
     * 8-bit encoded to get an accurate byte count.
     */
    protected function _byteCount(string $string): int
    {
        if (ini_get('mbstring.func_overload') > 0) {
            return mb_strlen($string, '8bit');
        }

        return strlen($string);
    }
}
