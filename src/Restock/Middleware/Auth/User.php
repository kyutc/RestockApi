<?php

declare(strict_types=1);

namespace Restock\Middleware\Auth;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Restock\Db\UserAccount;

class User implements MiddlewareInterface
{
    /*
     * Used to authenticate and grant permissions to users who have logged in.
     */

    private \Restock\Db\UserAccount $userAccount;

    public function __construct(\Restock\Db\UserAccount $userAccount)
    {
        $this->userAccount = $userAccount;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getHeader('X-RestockUserApiToken')[0] ?? '';

        if ($this->userAccount->ValidateUserApiToken($token)) {
            return $handler->handle($request);
        }

        return new JsonResponse(['result' => 'error', 'message' => 'Unauthenticated user.'], 403);
    }
}
