<?php

declare(strict_types=1);

namespace Restock\Controller;


use Doctrine\ORM\EntityManager;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Restock\Entity\Item;

class ItemController
{
    private \Restock\Db\Item $item;
    private EntityManager $entityManager;

    public function __construct(\Restock\Db\Item $item, EntityManager $entityManager)
    {
        $this->item = $item;
        $this->entityManager = $entityManager;
    }

    public function createItem(ServerRequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->getBody(), true);

        $groupId = $data['groupId'];

        $entityManager = $this->entityManager;

        // Create and persist new Item entities.
        $createdItems = [];
        foreach ($data['items'] as $itemData) {
            $group = $entityManager->getRepository(Group::class)->find($groupId);

            $item = new Item($group, $itemData['name'], $itemData['description'], $itemData['category'],
                $itemData['pantry_quantity'], $itemData['minimum_threshold'], $itemData['auto_add_to_shopping_list'],
                $itemData['shopping_list_quantity'], $itemData['auto_add_to_pantry']);

            $entityManager->persist($item);
            $createdItems[] = $item;
        }

        $entityManager->flush();

        // Return a JSON response with the created items and their properties.
        $response = [
            'result' => 'success',
            'items' => $createdItems,
        ];

        return new JsonResponse($response, 200);
    }

    public function updateItem(ServerRequestInterface $request): ResponseInterface
    {
        // Assuming the request body contains the JSON data.
        $data = json_decode($request->getBody(), true);

        $entityManager = $this->entityManager;

        // Update existing Item entities.
        $updatedItems = [];
        foreach ($data['items'] as $itemId => $itemData) {
            $item = $entityManager->getRepository(Item::class)->find($itemId);

            if ($item) {
                // Update item properties here...
                $item->setName($itemData['name']);
                $item->setDescription($itemData['description']);
                $item->setCategory($itemData['category']);
                $item->setPantryQuantity($itemData['pantry_quantity']);
                $item->setMinimumThreshold($itemData['minimum_threshold']);
                $item->setAutoAddToShoppingList($itemData['auto_add_to_shopping_list']);
                $item->setShoppingListQuantity($itemData['shopping_list_quantity']);
                $item->setDontAddToPantryOnPurchase($itemData['auto_add_to_pantry']);

                $entityManager->persist($item);
                $updatedItems[] = $item;
            }
        }

        $entityManager->flush();

        $response = [
            'result' => 'success',
            'items' => $updatedItems,
        ];

        return new JsonResponse($response, 200);
    }

    public function deleteItem(ServerRequestInterface $request): ResponseInterface
    {
        // Assuming the request body contains the JSON data with item IDs to delete.
        $data = json_decode($request->getBody(), true);

        $entityManager = $this->entityManager;

        // Delete specified Item entities.
        $deletedItems = [];
        foreach ($data['itemIds'] as $itemId) {
            $item = $entityManager->getRepository(Item::class)->find($itemId);

            if ($item) {
                $entityManager->remove($item);
                $deletedItems[] = $itemId;
            }
        }

        $entityManager->flush();

        // Return a JSON response indicating the deleted item IDs.
        $response = [
            'result' => 'success',
            'deleted_items' => $deletedItems,
        ];

        return new JsonResponse($response, 200);
    }

    public function getItemDetails(ServerRequestInterface $request): ResponseInterface
    {
        // Extract the group ID from the request, assuming it's part of the URL or request parameters.
        $groupId = $request->getAttribute('group_id');

        $entityManager = $this->entityManager;

        // Retrieve all items for the specified group.
        $items = $entityManager->getRepository(Item::class)->findBy(['group' => $groupId]);

        // Convert the items to an associative array and format them using __toString.
        $formattedItems = [];
        foreach ($items as $item) {
            $formattedItems[$item->getId()] = json_decode($item->__toString(), true);
        }

        // Return a JSON response with the group's items and their properties.
        $response = [
            'result' => 'success',
            'items' => $formattedItems,
        ];

        return new JsonResponse($response, 200);
    }

}