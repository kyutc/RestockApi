<?php

namespace Restock;

use Laminas\Diactoros\Response\JsonResponse;

class PResponse
{
    static public function badRequest(string $message)
    {
        return new JsonResponse([
            "result" => "error",
            "message" => $message
        ],
            400);
    }

    static public function serverErr(string $message)
    {
        return new JsonResponse([
            "result" => "error",
            "message" => $message
        ],
            500);
    }

    static public function created($payload = [])
    {
        return new JsonResponse(
            $payload,
            201
        );
    }

    static public function forbidden(string $message)
    {
        return new JsonResponse([
            "result" => "error",
            "message" => $message
        ],
            400);
    }

    static public function notFound()
    {
        return new JsonResponse([], 404);
    }

    static public function ok(array $payload = [])
    {
        return new JsonResponse(
            $payload,
            200
        );
    }
}