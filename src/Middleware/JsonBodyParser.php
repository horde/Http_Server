<?php

declare(strict_types=1);

namespace Horde\Http\Server\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * JSON Body Parser Middleware
 *
 * Parses JSON request bodies and makes them available via getParsedBody().
 * This solves the php://input non-seekable stream issue by reading the body
 * once and caching the parsed result in the request.
 *
 * Usage in route configuration:
 * ```php
 * $mapper->connect('api_endpoint', '/api/endpoint', [
 *     'controller' => MyApiController::class,
 *     'stack' => [JsonBodyParser::class],
 * ]);
 * ```
 *
 * In controllers, use getParsedBody() instead of reading getBody():
 * ```php
 * public function handle(ServerRequestInterface $request): ResponseInterface
 * {
 *     $body = $request->getParsedBody(); // array|null
 *     $username = $body['username'] ?? null;
 * }
 * ```
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Http_Server
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class JsonBodyParser implements MiddlewareInterface
{
    /**
     * Process the request and parse JSON body if present
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Only parse if Content-Type indicates JSON
        $contentType = $request->getHeaderLine('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            // Read body stream once - php://input is non-seekable
            $body = (string) $request->getBody();

            if ($body !== '') {
                $parsed = json_decode($body, true);

                // Only set parsedBody if JSON is valid
                if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                    $request = $request->withParsedBody($parsed);
                }
            }
        }

        return $handler->handle($request);
    }
}
