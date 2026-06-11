<?php

declare(strict_types=1);

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD-2-Clause
 * @package  Http_Server
 */

namespace Horde\Http\Server;

use Horde\Http\Cookie;
use Horde\Http\CookieList;
use Horde\Http\StrictCookie;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Server-side cookie glue over PSR-7 messages.
 *
 * Static utility methods. The substance (validation, formatting, parsing)
 * lives in `horde/Http`; this class only adapts those types to the PSR-7
 * message API:
 *
 * - {@see read()} : turn a request's parsed cookies into a typed
 *   {@see CookieList} for in-handler reads.
 * - {@see with()} : append a `Set-Cookie` header for a {@see Cookie} to
 *   a response on the way out.
 * - {@see clear()} : append a `Set-Cookie` header that deletes a named
 *   cookie. Domain and Path must match what was originally set or
 *   browsers see this as a different cookie and ignore it.
 */
final class Cookies
{
    /**
     * Don't instantiate.
     */
    private function __construct() {}

    /**
     * Read request cookies as a typed list.
     */
    public static function read(ServerRequestInterface $request): CookieList
    {
        /** @var array<string, string> $params */
        $params = $request->getCookieParams();
        return CookieList::fromCookieParams($params);
    }

    /**
     * Append a Set-Cookie header for this cookie to the response.
     *
     * Uses {@see ResponseInterface::withAddedHeader()} (not
     * `withHeader`) so multiple `Set-Cookie` headers can stack on one
     * response.
     */
    public static function with(ResponseInterface $response, Cookie $cookie): ResponseInterface
    {
        return $response->withAddedHeader('Set-Cookie', $cookie->toSetCookieHeader());
    }

    /**
     * Append a Set-Cookie header that deletes the named cookie.
     *
     * Path and Domain must match what was originally set, otherwise the
     * browser sees this as a different cookie and ignores the deletion.
     */
    public static function clear(
        ResponseInterface $response,
        string $name,
        string $path = '/',
        ?string $domain = null,
    ): ResponseInterface {
        $cookie = (new StrictCookie(
            cookieName: $name,
            cookiePath: $path,
            cookieDomain: $domain,
        ))->deletion();
        return self::with($response, $cookie);
    }
}
