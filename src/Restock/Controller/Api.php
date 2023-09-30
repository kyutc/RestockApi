<?php

namespace Restock\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Api
{
    private \Restock\Db\Register $reg;
    public function __construct(\Restock\Db\Register $reg) {
        $this->reg = $reg;
    }

    public function authTest(ServerRequestInterface $request): ResponseInterface {
        return new JsonResponse([
            'messsage'   => 'Seeing this means auth is successful',
        ], 200);
    }

    public function checkUsernameAvailable(ServerRequestInterface $request, array $args): ResponseInterface {
        if ($this->reg->CheckUsernameAvailability($args['username'])) {
            return new JsonResponse([],404); // Username is available
        }

        return new JsonResponse([],200); // Username is not available
    }

    public function registerNewUser(ServerRequestInterface $request): ResponseInterface {
        // TODO: Rate limiting and captcha.
        // TODO: Use tools instead of manually checking user input and creating errors

        $username = $request->getParsedBody()['username'];
        $password = $request->getParsedBody()['password'];

        // Consider: mb_strlen and is varchar/other data type multibyte aware in db?
        // TODO: Limit charset of username to A-Z, a-z, 0-9, -, _
        if (!is_string($username) || strlen($username) < 3 || strlen($username) > 30) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Username must be between 3 and 30 characters.'],
                400
            );
        }

        if (!is_string($password) || strlen($password) < 8) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Password must be 8 or more characters.'],
                400
            );
        }

        $this->reg->CreateAccount($username, $password);

        return new \Laminas\Diactoros\Response\JsonResponse(['result' => 'success'], 200);
    }

    public function userLogin(ServerRequestInterface $request): ResponseInterface {
        // TODO: Rate limiting
        // TODO: Token limiting ex. 10 before older tokens get replaced? Or allow no more than 1 token per user.

        $username = $request->getQueryParams()['username'];
        $password = $request->getQueryParams()['password'];

        if (!is_string($username) || !is_string($password)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Invalid username or password.'],
                400
            );
        }

        $token = '';
        $result = $this->reg->Login($username, $password, $token);

        if ($result) {
            return new JsonResponse([
                'result' => 'success',
                'token' => $token],
                200
            );
        }

        return new JsonResponse([
            'result' => 'error',
            'message' => 'Invalid username or password.'],
            400
        );
    }
}