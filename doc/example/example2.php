<?php

/**
 * Example 2:
 *
 * Setting up a request and a middleware which emits an own response
 * The Request will be handled by the middleware and never reach the handler.
 */
use Horde\Http\RequestFactory;
use Horde\Http\UriFactory;
use Horde\Http\ResponseFactory;
use Horde\Http\Server\RampageRequestHandler;
use Horde\Http\Server\RequestBuilder;
use Horde\Http\Server\ResponseWriterWeb;
use Horde\Http\Server\Runner;
use Horde\Http\Server\Middleware\Responder;
use Horde\Http\StreamFactory;

// Assuming this is the root
$autoloadFiles = [
    // library is root and installed
    dirname(__FILE__, 3) . '/vendor/autoload.php',
    // library is in vendor dir
    dirname(__FILE__, 5) . '/autoload.php',
];
foreach ($autoloadFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
        break;
    }
}

$requestFactory = new RequestFactory();
$streamFactory = new StreamFactory();
$uriFactory = new UriFactory();
$responseFactory = new ResponseFactory();

// Build the request from server variables.
// The RequestBuilder could easily be autowired by a DI container.
$requestBuilder = new RequestBuilder($requestFactory, $streamFactory, $uriFactory);
$request = $requestBuilder->withGlobalVariables()->build();

$middlewares = [
    new Responder($responseFactory, $streamFactory),
];
$handler = new RampageRequestHandler($responseFactory, $streamFactory, $middlewares);
$runner = new Runner($handler, new ResponseWriterWeb());
$runner->run($request);
