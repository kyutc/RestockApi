<?php

declare(strict_types=1);

namespace Restock\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Restock\Entity\Group;
use Restock\Entity\GroupMember;
use Restock\Entity\Session;
use Restock\Entity\User;
use Restock\ActionLogger;
use Restock\PResponse;

class UserController
{
    private EntityManager $entityManager;
    private ?User $user;

    public function __construct(EntityManager $entityManager, ?User $user)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    /**
     * Query whether a session is valid or not.
     *  i.e. Is the user logged in?
     *
     * GET /authTest
     * X-RestockUserApiToken: {token}
     * X-RestockApiToken: anything
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function authTest(ServerRequestInterface $request): ResponseInterface
    {
        return PResponse::ok(); // Provided session is valid
    }

    /**
     * Check whether a username exists.
     *
     * HEAD /user/{username:username}
     *
     * @REFACTOR    emails are the only unique user account property.
     *              Refactor this method and the route to see if an email is in use.
     *
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function checkUsernameAvailable(ServerRequestInterface $request, array $args): ResponseInterface
    {
        if (!$this->entityManager->getRepository('Restock\Entity\User')->findOneBy(['username' => $args['username']])) {
            return PResponse::notFound(); // Username is available
        }

        return PResponse::ok(); // Username is not available
    }

    /**
     * Register a new User.
     *
     * POST /user
     * X-RestockUserApiToken: {token}
     * X-RestockApiToken: anything
     * Content:
     *  email={user's email}
     *  username={new username}
     *  password={new password}
     *
     * Response:
     * {
     *  "id": {id},
     *  "name": {name}
     * }
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
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
            return PResponse::badRequest('Username must be between 3 and 30 characters.');
        }

        if ($this->entityManager->getRepository('Restock\Entity\User')->findBy(['email' => $email])) {
            return PResponse::badRequest('Email is already in use.');
        }

        if (!is_string($password) || strlen($password) < 8) {
            return PResponse::badRequest('Password must be 8 or more characters.');
        }

        $user = new User($username, $password, $email);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return PResponse::created($user->toArray());
    }


    /**
     * Create a new session.
     *
     * POST /session
     * Accept: application/json
     * X-RestockApiToken: anything
     * Content:
     *  password={password}
     *  email={email}
     *
     * Response:
     * {
     *  "id": "22",
     *  "name": "Blahbuffet",
     *  "session": "7rQjbcbpleehDA5UgA1GWKq5q4J5wdX/8ZA5hJc1PGk="
     * }
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function userLogin(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: Rate limiting
        // TODO: Token limiting ex. 10 before older tokens get replaced? Or allow no more than 1 token per user.

        $password = $request->getParsedBody()['password'];
        $email = $request->getParsedBody()['email'];

        if (!is_string($password) || !is_string($email)) {
            return PResponse::badRequest('Missing required field(s)');
        }

        /** @var User $user */
        if ($user = $this->entityManager->getRepository('Restock\Entity\User')->findOneBy(
            ['email' => $email]
        )) {
            // Validate stored password hash
            if (!password_verify($password, $user->getPassword())) {
                return PResponse::badRequest('Invalid email or password.');
            }

            // Email and hashed password matches user entry
            $session = new Session($user);
            $this->entityManager->persist($session);
            $this->entityManager->flush();

            return PResponse::created([
                ...$user->toArray(),
                'session' => $session->getToken()
            ]);
        }

        return PResponse::badRequest('Invalid email or password.');
    }

    /**
     * Delete the session.
     *
     *  DELETE /session
     *  Accept: application/json
     *  Content-Type: application/json
     *  X-RestockApiToken: anything
     *  X-RestockUserApiToken: {token}
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function userLogout(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->user;
        $user->getSessions()->clear();

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (OptimisticLockException|ORMException $e) {
            return PResponse::serverErr('Failed when updating database');
        }

        return PResponse::ok();
    }

    /**
     * Fetch user's details.
     *
     * GET /user/{user_id:number}
     * Accept: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function getUser(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception("Not implemented.");
    }

    /**
     * Update user.
     * Password is only required when setting a new password.
     *
     * PUT /user/{user_id}
     * Accept: application/json
     * Content-Type: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     * Content:
     *  {
     *      "new_name": "the blah",
     *
     *      "password": "Sharp_Gooser11",
     *      "new_password": "Quack_Attack!"
     *  }
     *
     * Response:
     * {
     *  "id": "24",
     *  "name": "Bandapan"
     * }
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function updateUser(ServerRequestInterface $request): ResponseInterface
    {
        $actionLogger = new ActionLogger($this->entityManager);
        $user = $this->user;
        $data = json_decode($request->getBody()->getContents(), true);

        if ($new_username = $data['new_username']) {
            $old_username = $user->getName();
            $user->setName($new_username);
            $groupMemberships = $user->getMemberDetails();
            foreach ($groupMemberships as $groupMembership) {
                $group = $groupMembership->getGroup();
                $actionLogger->createActionLog($group, 'Username changed from ' . $old_username . ' to ' . $new_username);
            }
        }

        if ($new_password = $data['new_password']) {
            $password = $data['password']; // Original password is required to set new password

            if (!is_string($password) || !password_verify($password, $user->getPassword())) {
                // Bad original password
                return PResponse::forbidden('Could not verify password.');
            }
            if (!is_string($new_password) || strlen($new_password) < 8) {
                // Invalid new password
                return PResponse::badRequest('New password must be 8 or more characters.');
            }
            $user->setPassword($new_password);
        }

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (ORMException $e) {
            PResponse::serverErr('Failed when updating database');
        }

        return PResponse::ok($user->toArray());
    }

    /**
     * Delete a user, all their sessions, and all their owned groups.
     *
     * DELETE /user/{user_id:number}
     *  Accept: application/json
     *  X-RestockApiToken: anything
     *  X-RestockUserApiToken: {token}
     *
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     */
    public function deleteUser(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $user = $this->user;
        $owned_groups = $user->getMemberDetails()
            ->filter(fn(GroupMember $group_member) => $group_member->getRole() === GroupMember::OWNER)
            ->map(fn(GroupMember $group_member) => $group_member->getGroup());

        try { // Delete each group where the user is the owner, then delete the user
            foreach ($owned_groups as $g) {
                $this->entityManager->remove($g);
            }
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        } catch (ORMException $e) {
            return PResponse::serverErr('Failed to delete account.');
        }

        return PResponse::ok();
    }
}