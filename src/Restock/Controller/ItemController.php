<?php

declare(strict_types=1);

namespace Restock\Controller;


use Doctrine\ORM\EntityManager;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Restock\Entity\Group;
use Restock\Entity\GroupMember;
use Restock\Entity\Item;
use Restock\Entity\User;
use Restock\ActionLogger;

class ItemController
{
    private EntityManager $entityManager;
    private User $user;

    public function __construct(EntityManager $entityManager, User $user, ActionLogger $actionLogger)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->actionLogger = $actionLogger;
    }

    /**
     * Create a new item
     *
     * POST /group/{group_id}/item
     * Accept: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     * Content:
     *  name={name}
     *  description={description}
     *  category={category#color}
     *  pantry_quantity={quantity}
     *  minimum_threshold={quantity}
     *  auto_add_to_shopping_list={boolean}
     *  shopping_list_quantity={quantity}
     *  dont_add_to_pantry_on_purchase={boolean}
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createItem(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $entityManager = $this->entityManager;
        $user = $this->user;
        $data = $request->getParsedBody();

        $group_member = $entityManager->getRepository(GroupMember::class)->findOneBy([
            'group' => $args['group_id'],
            'user' => $user->getId()
        ]);

        if (is_null($group_member)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of that group'
            ], 200);
        }

        $item = new Item(
            $group_member->getGroup(),
            $data['name'],
            $data['description'],
            $data['category'],
            intval($data['pantry_quantity']),
            intval($data['minimum_threshold']),
            boolval($data['auto_add_to_shopping_list']),
            intval($data['shopping_list_quantity']),
            boolval($data['dont_add_to_pantry_on_purchase'])
        );


        $entityManager->persist($item);
        $entityManager->flush();
        $this->actionLogger->logItemCreated($item);

        // Return a JSON response with the created items and their properties.
        $response = [
            'result' => 'success',
            'item' => "{$item}",
        ];

        return new JsonResponse($response, 200);
    }

    /**
     * Update an existing item
     *
     * PUT /group/{group_id}/item/{item_id} \
     * Accept: application/json
     * Content-Type: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     * Content:
     *  {
     *      "name": "{name}",
     *      "description": "{description}",
     *      "category": "{category#color}",
     *      "pantry_quantity": "{quantity}",
     *      "minimum_threshold": "{quantity}",
     *      "auto_add_to_shopping_list": "{boolean}",
     *      "shopping_list_quantity": "{quantity}",
     *      "dont_add_to_pantry_on_purchase": "{boolean}"
     *  }
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateItem(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $entityManager = $this->entityManager;
        $user = $this->user;

        $data = json_decode($request->getBody()->getContents(), true);

        $group_member = $entityManager->getRepository(GroupMember::class)->findOneBy([
            'group' => $args['group_id'],
            'user' => $user->getId()
        ]);

        if (is_null($group_member)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of that group'
            ], 200);
        }

        $item = $entityManager->getRepository(Item::class)->find($args['item_id']);
        if (!$item) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'That item doesn\'t exist'
            ],
            200
            );
        }

        // Update item properties here...
        if (isset($data['name'])) $item->setName($data['name']);
        if (isset($data['description'])) $item->setDescription($data['description']);
        if (isset($data['category'])) $item->setCategory($data['category']);
        if (isset($data['pantry_quantity'])) $item->setPantryQuantity(intval($data['pantry_quantity']));
        if (isset($data['minimum_threshold'])) $item->setMinimumThreshold(intval($data['minimum_threshold']));
        if (isset($data['auto_add_to_shopping_list'])) $item->setAutoAddToShoppingList(boolval($data['auto_add_to_shopping_list']));
        if (isset($data['shopping_list_quantity'])) $item->setShoppingListQuantity(intval($data['shopping_list_quantity']));
        if (isset($data['auto_add_to_pantry'])) $item->setDontAddToPantryOnPurchase(boolval($data['auto_add_to_pantry']));

        $entityManager->persist($item);
        $entityManager->flush();
        $this->actionLogger->logItemUpdated($item);

        $response = [
            'result' => 'success',
            'items' => "{$item}",
        ];

        return new JsonResponse($response, 200);
    }

    /**
     * Delete an existing item
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteItem(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $user = $this->user;
        $entityManager = $this->entityManager;

        $group_member = $entityManager->getRepository(GroupMember::class)->findOneBy([
            'group' => $args['group_id'],
            'user' => $user->getId()
        ]);

        if (is_null($group_member)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of that group'
            ], 200);
        }

        $item = $entityManager->getRepository(Item::class)->find($args['item_id']);
        if (!$item) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'That item doesn\'t exist'
            ],
                200
            );
        }

        $entityManager->remove($item);
        $entityManager->flush();
        $this->actionLogger->logItemDeleted($item);

        $response = [
            'result' => 'success',
            'message' => 'Item deleted'
        ];

        return new JsonResponse($response, 200);
    }

}