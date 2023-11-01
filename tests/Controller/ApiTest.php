<?php

namespace Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\HttpClient\HttpClient;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertIsArray;

class ApiTest extends TestCase
{
    const API_TOKEN = 'anything';
    const BASE_URI = 'https://api.cpsc4900.local/api/v1/';
    const HEADERS = [
        'Accept' => 'application/json',
        'X-RestockApiToken' => self::API_TOKEN
    ];

    const USERNAME = 'Usermane';
    const PASSWORD = 'aPssward';

    private \Symfony\Contracts\HttpClient\HttpClientInterface $client;

    protected function setUp(): void
    {
        $this->client = HttpClient::createForBaseUri(
            self::BASE_URI,
            [
                'headers' => self::HEADERS,
                'verify_peer' => false,
            ]
        );
    }

    private function assertJsonResponse(
        ResponseInterface $response,
        int $expected_status,
        mixed $expected_data = false,
    ): void {
        $json = $response->getContent(false);
        assertEquals($response->getStatusCode(), $expected_status, 'status did not match');
        if ($expected_data) {
            $data = json_decode($json, true);
            assertEquals(0, count(array_diff_assoc($data, $expected_data)), 'response body did not match');
        }
    }

    public function testRegistration(): void
    {
        // Check that user doesn't exist
        $this->assertJsonResponse(
            $this->client->request('HEAD', 'user/' . self::USERNAME),
            404
        );

        // Register with username too short
        $this->assertJsonResponse(
            $this->client->request('POST', 'user', [
                'body' => [
                    'username' => substr(self::USERNAME, 0, 2),
                    'password' => ' '
                ]
            ]),
            400,
            [
                'result' => 'error',
                'message' => 'Username must be between 3 and 30 characters.'
            ]
        );

        // Register with name too long
        $this->assertJsonResponse(
            $this->client->request('POST', 'user', [
                'body' => ['username' => str_pad(self::USERNAME, 31, self::USERNAME), 'password' => ' ']
            ]),
            400,
            [
                'result' => 'error',
                'message' => 'Username must be between 3 and 30 characters.'
            ]
        );

        // Register with password too short
        $this->assertJsonResponse(
            $this->client->request('POST', 'user', [
                'body' => [
                    'username' => self::USERNAME, 31, self::USERNAME,
                    'password' => substr(self::PASSWORD, 0, 7)
                ]
            ]),
            400,
            [
                'result' => 'error',
                'message' => 'Password must be 8 or more characters.'
            ]
        );

        // Successfully register
        $this->assertJsonResponse(
            $this->client->request('POST', 'user', [
                'body' => ['username' => self::USERNAME, 'password' => self::PASSWORD]
            ]),
            200,
            ['result' => 'success']
        );

        # TODO: Attempt to register again

        // Check that user now exists
        $this->assertJsonResponse(
            $this->client->request('HEAD', 'user/' . self::USERNAME),
            200
        );
    }
//
//    public function testLogin(): void
//    {
//    }
//
//    # Todo: getUserAccount and updateUserAccount methods
//
//    public function testLogout(): void
//    {
//    }
//
//    public function testDeleteUserAccount(): void
//    {
//    }
}