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

    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getHeader('X-RestockUserApiToken')[0];
        if (!$token) {
            // Session header not defined or field not provided
            return new JsonResponse(
                [
                    'result' => 'error',
                    'message' => 'Missing header(s)'
                ],
                403
            );
        }

        /** @var Session $session */
        if ($session = $this->entityManager
            ->getRepository('Restock\Entity\Session')
            ->findOneBy(['token' => $token])
        ) {
            $session->setLastUsedDate();
            return $handler->handle($request);
        }

        return new JsonResponse(['result' => 'error', 'message' => 'Unauthenticated user.'], 403);
    }
}
