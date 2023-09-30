<?php

declare(strict_types=1);

namespace Restock\Middleware\Auth;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\RedirectResponse;

class Api implements MiddlewareInterface
{
    /*
     * Used to validate an unauthenticated API access token.
     */

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getHeader('X-RestockApiToken');

        if (count($token) > 0) {
            // Client has X-RestockApiToken header defined, good enough to prevent CSRF
            return $handler->handle($request);
        }

        // Client does not have the header defined, don't trust request
        return new JsonResponse(['error' => 'Invalid API access token.'], 403);
    }
}
