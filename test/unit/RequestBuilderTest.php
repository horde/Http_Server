<?php

declare(strict_types=1);

namespace Horde\Http\Server\Test\Unit;

use Horde\Http\Server\RequestBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(RequestBuilder::class)]
class RequestBuilderTest extends TestCase
{
    private array $originalServer;
    private array $originalCookie;
    private array $originalGet;
    private array $originalPost;
    private array $originalFiles;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
        $this->originalCookie = $_COOKIE;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalFiles = $_FILES;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        $_COOKIE = $this->originalCookie;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_FILES = $this->originalFiles;
    }

    private function setMinimalServerVars(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_SERVER['HTTP_HOST'] = 'example.org';
        $_SERVER['SERVER_PORT'] = '443';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_COOKIE = [];
        $_GET = [];
        $_POST = [];
        $_FILES = [];
    }

    public function testConstructWithDefaults(): void
    {
        $builder = new RequestBuilder();
        $this->assertInstanceOf(RequestBuilder::class, $builder);
    }

    public function testBuildReturnsServerRequest(): void
    {
        $this->setMinimalServerVars();
        $builder = new RequestBuilder();
        $request = $builder->withGlobalVariables()->build();

        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame('GET', $request->getMethod());
    }

    public function testWithGlobalVariablesPopulatesUri(): void
    {
        $this->setMinimalServerVars();
        $_SERVER['REQUEST_URI'] = '/path/to/resource';
        $_SERVER['QUERY_STRING'] = 'foo=bar';

        $builder = new RequestBuilder();
        $request = $builder->withGlobalVariables()->build();

        $this->assertSame('/path/to/resource', $request->getUri()->getPath());
        $this->assertSame('foo=bar', $request->getUri()->getQuery());
    }

    public function testWithGlobalVariablesHttpsScheme(): void
    {
        $this->setMinimalServerVars();
        $builder = new RequestBuilder();
        $request = $builder->withGlobalVariables()->build();

        $this->assertSame('https', $request->getUri()->getScheme());
    }

    public function testWithGlobalVariablesHttpsFromEnvVar(): void
    {
        $this->setMinimalServerVars();
        unset($_SERVER['REQUEST_SCHEME']);
        $_SERVER['HTTPS'] = 'on';

        $builder = new RequestBuilder();
        $request = $builder->withGlobalVariables()->build();

        $this->assertSame('https', $request->getUri()->getScheme());
    }

    public function testWithGlobalVariablesHttpsOff(): void
    {
        $this->setMinimalServerVars();
        unset($_SERVER['REQUEST_SCHEME']);
        $_SERVER['HTTPS'] = 'off';

        $builder = new RequestBuilder();
        $request = $builder->withGlobalVariables()->build();

        $this->assertSame('http', $request->getUri()->getScheme());
    }

    public function testWithGlobalVariablesDefaultsToHttps(): void
    {
        $this->setMinimalServerVars();
        unset($_SERVER['REQUEST_SCHEME'], $_SERVER['HTTPS']);

        $builder = new RequestBuilder();
        $request = $builder->withGlobalVariables()->build();

        $this->assertSame('https', $request->getUri()->getScheme());
    }

    public function testWithHeadersStringValue(): void
    {
        $this->setMinimalServerVars();
        $builder = new RequestBuilder();
        $result = $builder->withGlobalVariables()->withHeaders([
            'X-Custom' => 'value',
        ]);

        $this->assertSame($builder, $result);
        $request = $builder->build();
        $this->assertSame('value', $request->getHeaderLine('X-Custom'));
    }

    public function testWithHeadersArrayValue(): void
    {
        $this->setMinimalServerVars();
        $builder = new RequestBuilder();
        $builder->withGlobalVariables()->withHeaders([
            'Accept' => ['text/html', 'application/json'],
        ]);

        $request = $builder->build();
        $this->assertSame('text/html, application/json', $request->getHeaderLine('Accept'));
    }

    public function testWithHeadersThrowsOnNonStringValue(): void
    {
        $this->setMinimalServerVars();
        $builder = new RequestBuilder();
        $builder->withGlobalVariables();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header value must be a string or an array of strings');
        $builder->withHeaders(['X-Bad' => 123]);
    }

    public function testWithHeadersThrowsOnNonStringInArray(): void
    {
        $this->setMinimalServerVars();
        $builder = new RequestBuilder();
        $builder->withGlobalVariables();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header value must be a string or an array of strings');
        $builder->withHeaders(['X-Bad' => ['valid', 42]]);
    }
}
