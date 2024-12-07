<?php

namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Start the session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Handle the request
        $response = $handler->handle($request);

        // Close the session
        session_write_close();

        return $response;
    }
}
