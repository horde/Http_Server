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

namespace Horde\Http\Server\Test\Unit;

use Horde\Http\CookieList;
use Horde\Http\ResponseFactory;
use Horde\Http\SameSite;
use Horde\Http\Server\Cookies;
use Horde\Http\StrictCookie;
use Horde\Http\UriFactory;
use Horde\Http\ServerRequest;
use Horde\Http\StreamFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Cookies::class)]
class CookiesTest extends TestCase
{
    private function request(array $cookieParams = []): ServerRequest
    {
        $uri = (new UriFactory())->createUri('http://example.com/');
        $stream = (new StreamFactory())->createStream('');
        $request = new ServerRequest('GET', $uri, [], $stream, '1.1', []);
        return $request->withCookieParams($cookieParams);
    }

    private function response()
    {
        return (new ResponseFactory())->createResponse(200);
    }

    // ---------------------------------------------------------------
    // read()
    // ---------------------------------------------------------------

    #[Test]
    public function readReturnsCookieList(): void
    {
        $request = $this->request(['session' => 'abc', 'theme' => 'dark']);
        $cookies = Cookies::read($request);

        self::assertInstanceOf(CookieList::class, $cookies);
        self::assertSame('abc', $cookies->get('session'));
        self::assertSame('dark', $cookies->get('theme'));
    }

    #[Test]
    public function readEmptyRequestProducesEmptyList(): void
    {
        $cookies = Cookies::read($this->request([]));
        self::assertSame([], $cookies->toArray());
    }

    // ---------------------------------------------------------------
    // with()
    // ---------------------------------------------------------------

    #[Test]
    public function withAppendsSetCookieHeader(): void
    {
        $response = Cookies::with(
            $this->response(),
            new StrictCookie(cookieName: 'session', cookieValue: 'abc'),
        );
        self::assertSame(
            ['session=abc; Path=/; SameSite=Lax'],
            $response->getHeader('Set-Cookie'),
        );
    }

    #[Test]
    public function withStacksMultipleCookies(): void
    {
        // Two with() calls on one response must produce two Set-Cookie
        // headers, not overwrite each other. This is what PSR-7's
        // withAddedHeader() guarantees vs withHeader().
        $response = Cookies::with(
            $this->response(),
            new StrictCookie(cookieName: 'a', cookieValue: '1'),
        );
        $response = Cookies::with(
            $response,
            new StrictCookie(cookieName: 'b', cookieValue: '2'),
        );
        $headers = $response->getHeader('Set-Cookie');
        self::assertCount(2, $headers);
        self::assertStringContainsString('a=1', $headers[0]);
        self::assertStringContainsString('b=2', $headers[1]);
    }

    #[Test]
    public function withReturnsNewResponseInstance(): void
    {
        $original = $this->response();
        $changed = Cookies::with(
            $original,
            new StrictCookie(cookieName: 'x', cookieValue: 'y'),
        );
        self::assertNotSame($original, $changed);
        self::assertSame([], $original->getHeader('Set-Cookie'));
    }

    // ---------------------------------------------------------------
    // clear()
    // ---------------------------------------------------------------

    #[Test]
    public function clearEmitsMaxAgeZero(): void
    {
        $response = Cookies::clear($this->response(), 'session');
        $header = $response->getHeader('Set-Cookie')[0];
        self::assertStringContainsString('session=', $header);
        self::assertStringContainsString('Max-Age=0', $header);
    }

    #[Test]
    public function clearPreservesPathAndDomain(): void
    {
        $response = Cookies::clear(
            $this->response(),
            'session',
            '/horde',
            'example.com',
        );
        $header = $response->getHeader('Set-Cookie')[0];
        self::assertStringContainsString('Path=/horde', $header);
        self::assertStringContainsString('Domain=example.com', $header);
        self::assertStringContainsString('Max-Age=0', $header);
    }

    #[Test]
    public function clearDefaultsToRootPath(): void
    {
        $response = Cookies::clear($this->response(), 'session');
        $header = $response->getHeader('Set-Cookie')[0];
        self::assertStringContainsString('Path=/', $header);
        self::assertStringNotContainsString('Domain=', $header);
    }

    // ---------------------------------------------------------------
    // Round trip: write then read on a fresh request
    // ---------------------------------------------------------------

    #[Test]
    public function writeThenReadInteroperate(): void
    {
        // Write a cookie on a response.
        $response = Cookies::with(
            $this->response(),
            new StrictCookie(
                cookieName: 'session',
                cookieValue: 'abc123',
                cookieSecure: true,
                cookieHttpOnly: true,
                cookieSameSite: SameSite::Strict,
            ),
        );

        // The framework would put the header on the wire; the next request
        // arrives with the cookie in $_COOKIE / getCookieParams(). Simulate
        // the round trip and read it back.
        $cookies = Cookies::read($this->request(['session' => 'abc123']));
        self::assertSame('abc123', $cookies->get('session'));
        self::assertNotEmpty($response->getHeader('Set-Cookie'));
    }
}
