<?php

declare(strict_types=1);

namespace Restock\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Restock\Entity\Session;
use Restock\Entity\User;

class UserController
{
    private \Restock\Db\UserAccount $userAccount;
    private EntityManager $entityManager;

    public function __construct(\Restock\Db\UserAccount $userAccount, EntityManager $entityManager)
    {
        $this->userAccount = $userAccount;
        $this->entityManager = $entityManager;
    }

    public function checkUsernameAvailable(ServerRequestInterface $request, array $args): ResponseInterface
    {
        if ($this->userAccount->CheckUsernameAvailability($args['username'])) {
            return new JsonResponse([], 404); // Username is available
        }

        return new JsonResponse([], 200); // Username is not available
    }

    public function createUser(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: Rate limiting and captcha.
        // TODO: Use tools instead of manually checking user input and creating errors

        $username = $request->getParsedBody()['username'];
        $password = $request->getParsedBody()['password'];
        $email = $request->getParsedBody()['email'];

        // Consider: mb_strlen and is varchar/other data type multibyte aware in db?
        // TODO: Limit charset of username to A-Z, a-z, 0-9, -, _
        if (!is_string($username) || strlen($username) < 3 || strlen($username) > 30) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Username must be between 3 and 30 characters.'
            ],
                400
            );
        }

        if (!$this->userAccount->CheckUsernameAvailability($username)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Username is already taken.'
            ],
                400
            );
        }

        if (!is_string($password) || strlen($password) < 8) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Password must be 8 or more characters.'
            ],
                400
            );
        }

        $user = new User($username, $password, $email);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new \Laminas\Diactoros\Response\JsonResponse(['result' => 'success'], 200);
    }

    public function editUser(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception('Not implemented.');
    }

    public function userLogin(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: Rate limiting
        // TODO: Token limiting ex. 10 before older tokens get replaced? Or allow no more than 1 token per user.

        $password = $request->getParsedBody()['password'];
        $email = $request->getParsedBody()['email'];

        if (!is_string($password) || !is_string($email)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Missing required field(s)'
            ],
                401
            );
        }

        /** @var User $user */
        if ($user = $this->entityManager->getRepository('Restock\Entity\User')->findOneBy(
            ['email' => $email]
        )) {
            // Validate stored password hash
            if (!password_verify($password, $user->getPassword())) {
                return new JsonResponse([
                    'result' => 'error',
                    'message' => 'Invalid email or password.'
                ],
                    401
                );
            }

            // Email and hashed password matches user entry
            $session = new Session($user);
            $this->entityManager->persist($session);
            $this->entityManager->flush();

            return new JsonResponse([
                'result' => 'success',
                'token' => $session->getToken()
            ],
                201
            );
        }

        return new JsonResponse([
            'result' => 'error',
            'message' => 'Invalid email or password.'
        ],
            401
        );
    }

    public function userLogout(ServerRequestInterface $request): ResponseInterface
    {
        /** @var User $user */
        $user = $_SESSION['user'];
        $user->getSessions()->clear();

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (OptimisticLockException|ORMException $e) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Failed when updating database'
            ],
                500
            );
        }

        return new JsonResponse([
            'result' => 'success',
            'message' => 'You have been logged out.'
        ],
            200
        );
    }

    public function getUser(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception("Not implemented.");
    }

    public function updateUser(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception("Not implemented.");
    }

    public function deleteUser(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $token = $request->getHeader('X-RestockUserApiToken')[0];
        $user_id = (int)$args['user_id'];

        if ($this->userAccount->DeleteAccount($user_id, $token)) {
            return new JsonResponse([
                'result' => 'success',
                'message' => 'Account has been deleted.'
            ],
                200
            );
        }

        // See comments in "DeleteAccount" in short: need better auth flow and error checking and reporting flow
        return new JsonResponse([
            'result' => 'error',
            'message' => 'Failed to delete account.'
        ],
            500
        );
    }
}