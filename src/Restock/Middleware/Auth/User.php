<?php

declare(strict_types=1);

namespace Restock\Middleware\Auth;

use Doctrine\ORM\EntityManager;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Restock\Db\UserAccount;
use Restock\Entity\Session;

class User implements MiddlewareInterface
{
    /*
     * Used to authenticate and grant permissions to users who have logged in.
     */

    private ?\Restock\Entity\User $user;

    public function __construct(?\Restock\Entity\User $user)
    {
        $this->user = $user;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (is_null($this->user)) {
            // Session header not defined or field not provided
            return new JsonResponse(
                [
                    'result' => 'error',
                    'message' => 'Failed to authenticate'
                ],
                403
            );
        }

        $_SESSION['user'] = $this->user;
        return $handler->handle($request);
    }
}
