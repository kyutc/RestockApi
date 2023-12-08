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
use Restock\PResponse;

class ItemController
{
    private EntityManager $entityManager;
    private User $user;

    public function __construct(EntityManager $entityManager, User $user)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;
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
     *  add_to_pantry_on_purchase={boolean}
     *
     * Response:
     * {
     *  "id": "15",
     *  "group_id": "2",
     *  "name": "ketchup",
     *  "description": "sugary tomato paste",
     *  "category": "deafult;#000000",
     *  "pantry_quantity": "62",
     *  "minimum_threshold": "40",
     *  "auto_add_to_shopping_list": "true",
     *  "shopping_list_quantity": "0",
     *  "add_to_pantry_on_purchase": "false"
     * }
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createItem(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $actionLogger = new ActionLogger($this->entityManager);
        $entityManager = $this->entityManager;
        $user = $this->user;
        $data = $request->getParsedBody();

        $group_member = $entityManager->getRepository(GroupMember::class)->findOneBy([
            'group' => $args['group_id'],
            'user' => $user->getId()
        ]);

        if (is_null($group_member)) {
            return PResponse::forbidden('That group does not exist or you are not a member of that group');
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
            boolval($data['add_to_pantry_on_purchase'])
        );


        $entityManager->persist($item);
        $entityManager->flush();
        $actionLogger->createActionLog($group_member->getGroup(), 'Item ' . $item->getName() . ' created');

        // Return a JSON response with the created items and their properties.
        return PResponse::created($item->toArray());
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
     *      "add_to_pantry_on_purchase": "{boolean}"
     *  }
     *
     * Response:
     *  {
     *   "id": "15",
     *   "group_id": "2",
     *   "name": "ketchup",
     *   "description": "sugary tomato paste",
     *   "category": "deafult;#000000",
     *   "pantry_quantity": "62",
     *   "minimum_threshold": "40",
     *   "auto_add_to_shopping_list": "true",
     *   "shopping_list_quantity": "0",
     *   "add_to_pantry_on_purchase": "false"
     *  }
     *
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateItem(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $actionLogger = new ActionLogger($this->entityManager);
        $entityManager = $this->entityManager;
        $user = $this->user;

        $data = json_decode($request->getBody()->getContents(), true);

        $group_member = $entityManager->getRepository(GroupMember::class)->findOneBy([
            'group' => $args['group_id'],
            'user' => $user->getId()
        ]);

        if (is_null($group_member)) {
            return PResponse::forbidden('That group does not exist or you are not a member of that group');
        }

        $item = $entityManager->getRepository(Item::class)->find($args['item_id']);
        if (!$item) {
            return PResponse::forbidden('That item doesn\'t exist or doesn\'t belong to your group');
        }

        // Update item properties here...
        $item->setName($data['name'] ?? $item->getName());
        $item->setDescription($data['description'] ?? $item->getDescription());
        $item->setCategory($data['category'] ?? $item->getCategory());
        $item->setPantryQuantity(intval($data['pantry_quantity'] ?? $item->getPantryQuantity()));
        $item->setMinimumThreshold(intval($data['minimum_threshold'] ?? $item->getMinimumThreshold()));
        $item->setAutoAddToShoppingList(boolval($data['auto_add_to_shopping_list'] ?? $item->isAutoAddToShoppingList()));
        $item->setShoppingListQuantity(intval($data['shopping_list_quantity'] ?? $item->getShoppingListQuantity()));
        $item->setAddToPantryOnPurchase(boolval($data['auto_add_to_pantry'] ?? $item->isAddToPantryOnPurchase()));

        $entityManager->persist($item);
        $entityManager->flush();
        $actionLogger->createActionLog($group_member->getGroup(), 'Item ' . $item->getName() . ' updated');

        return PResponse::ok($item->toArray());
    }

    /**
     * Delete an existing item
     *
     * DELETE /group/{group_id}/item/{item_id} \
     * Accept: application/json
     * Content-Type: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteItem(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $actionLogger = new ActionLogger($this->entityManager);
        $user = $this->user;
        $entityManager = $this->entityManager;

        $group_member = $entityManager->getRepository(GroupMember::class)->findOneBy([
            'group' => $args['group_id'],
            'user' => $user->getId()
        ]);

        if (is_null($group_member)) {
            return PResponse::forbidden('That group does not exist or you are not a member of that group');
        }

        $item = $entityManager->getRepository(Item::class)->find($args['item_id']);
        if (!$item) {
            return PResponse::forbidden('That group does not exist or you are not a member of that group');
        }

        $entityManager->remove($item);
        $entityManager->flush();
        $actionLogger->createActionLog($group_member->getGroup(), 'Item ' . $item->getName() . ' deleted');

        return PResponse::ok();
    }

}