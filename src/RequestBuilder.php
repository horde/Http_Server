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
        $headers = getallheaders();

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
}
