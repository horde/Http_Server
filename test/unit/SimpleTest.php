<?php
namespace Horde\Http\Server\Test\Unit;
use \PHPUnit\Framework\TestCase;
use Horde\Http\RequestFactory;
use Horde\Http\ResponseFactory;
use Horde\Http\StreamFactory;
use Horde\Http\Server\RampageRequestHandler;
use Psr\Http\Message\ResponseInterface;

/**
 * @author     Ralf Lang <lang@b1-systems.de>
 * @license    http://www.horde.org/licenses/bsd LGPL BSD-3-Clause
 * @category   Horde
 * @package    Http_Server
 * @subpackage UnitTests
 */
class SimpleTest extends TestCase
{
    public function testRunnerSetup()
    {
        // Production code would use Injector/DIC instead
        $responseFactory = new ResponseFactory;
        $streamFactory = new StreamFactory;
        $handler = new RampageRequestHandler($responseFactory, $streamFactory);
        $requestFactory = new RequestFactory();
        $request = $requestFactory->createServerRequest('GET', 'https://www.horde.org');
        $response = $handler->handle($request);
        $this->assertInstanceOf(ResponseInterface::class, $handler->handle($request));
    }

}