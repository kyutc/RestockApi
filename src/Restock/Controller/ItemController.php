<?php

declare(strict_types=1);

namespace Restock\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ItemController
{
    private \Restock\Db\Item $item;

    public function __construct(\Restock\Db\Item $item)
    {
        $this->item = $item;
    }

    public function createItem(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception("Not implemented.");
    }

    public function updateItem(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception("Not implemented.");
    }

    public function deleteItem(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception("Not implemented.");
    }

    public function getItemDetails(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception("Not implemented.");
    }

}