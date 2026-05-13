<?php

/**
 * Test bootstrap for Http_Server.
 *
 * Provides polyfills needed for CLI testing, then loads the Composer autoloader.
 */

namespace Horde\Http\Server {
    if (!function_exists('Horde\Http\Server\getallheaders')) {
        /**
         * Polyfill for getallheaders() in CLI context.
         *
         * RequestBuilder calls getallheaders() without a leading backslash,
         * so PHP resolves it in the Horde\Http\Server namespace first.
         * Defining it there lets unit tests run under CLI SAPI.
         */
        function getallheaders(): array
        {
            return [];
        }
    }
}

namespace {
    require_once __DIR__ . '/../vendor/autoload.php';
}
