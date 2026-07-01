<?php

namespace Horde\Http\Server;

use Horde\Http\RequestFactory;
use Horde\Http\StreamFactory;
use Horde\Http\UriFactory;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use InvalidArgumentException;
use function getallheaders;
use function function_exists;

/**
 * RequestBuilder applies global state or other resources to a ServerRequest
 *
 * Builder uses a fluent interface returning itself.
 * To return the produced request, run ->build()
 */
class RequestBuilder
{
    private ServerRequestInterface $request;
    private ServerRequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private UriFactoryInterface $uriFactory;

    public function __construct(
        ServerRequestFactoryInterface $requestFactory = new RequestFactory(),
        StreamFactoryInterface $streamFactory = new StreamFactory(),
        UriFactoryInterface $uriFactory = new UriFactory(),
    ) {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->uriFactory = $uriFactory;
    }

    /**
     * Create a new server request populated with global state
     */
    public function withGlobalVariables(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (!empty($_SERVER['REQUEST_SCHEME'])) {
            $scheme = $_SERVER['REQUEST_SCHEME'];
        } elseif (!empty($_SERVER['HTTPS'])) {
            if ($_SERVER['HTTPS'] == 'off') {
                $scheme = 'http';
            } else {
                $scheme = 'https';
            }
        } else {
            // Default to https
            $scheme = 'https';
        }
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        // Always set it, the Uri object will strip it if default
        $port = $_SERVER['SERVER_PORT'] ?? '';
        $path = $_SERVER['REQUEST_URI'] ?? '';

        $uriString = sprintf("%s://%s", $scheme, $host);

        $uri = $this->uriFactory->createUri($uriString)
        ->withPort($port)
        ->withPath($path)
        ->withQuery($queryString);
        $body = $this->streamFactory->createStreamFromFile('php://input', 'r+');

        $protocol = !empty($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';
        $headers = function_exists('getallheaders') ? getallheaders() : self::headersFromServer($_SERVER);

        $this->request = $this->requestFactory
            ->createServerRequest($method, $uri, $_SERVER)
            ->withBody($body)
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles($_FILES)
            ->withProtocolVersion($protocol);
        $this->withHeaders($headers);
        return $this;
    }

    /**
     * Add headers from list
     *
     * @param mixed[] $headers
     * @return self
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $header => $value) {
            if (is_array($value)) {
                foreach ($value as $listItem) {
                    if (!is_string($listItem)) {
                        throw new InvalidArgumentException('Header value must be a string or an array of strings');
                    }
                }
            } else {
                if (!is_string($value)) {
                    throw new InvalidArgumentException('Header value must be a string or an array of strings');
                }
            }
            $this->request = $this->request->withHeader($header, $value);
        }
        return $this;
    }

    public function build(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Reconstruct HTTP request headers from $_SERVER.
     *
     * Fallback for SAPIs where the getallheaders() function is not
     * available (CLI, and some FPM builds). Mirrors the shape returned
     * by getallheaders(): header names are the natural "Header-Name"
     * spelling, values are strings.
     *
     * @param array<string, mixed> $server $_SERVER superglobal or an
     *                                     equivalent array.
     * @return array<string, string>
     */
    private static function headersFromServer(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (str_starts_with($key, 'HTTP_')) {
                $name = substr($key, 5);
            } elseif ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH' || $key === 'CONTENT_MD5') {
                $name = $key;
            } else {
                continue;
            }
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))));
            $headers[$name] = $value;
        }
        return $headers;
    }
}
