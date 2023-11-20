<?php

declare(strict_types=1);

namespace Restock\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Restock\Entity\Recipe;
use Restock\Entity\User;
use Doctrine\ORM\EntityManager;
use Restock\Entity\Group;
Use Exception;

class RecipeController {
    private EntityManager $entityManager;
    private User $user;

    public function __construct(EntityManager $entityManager, User $user) {
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    public function createRecipe(ServerRequestInterface $request): ResponseInterface {
        try {
            $data = $request->getParsedBody();
            $user = $this->user;
            $recipe = new Recipe($user, 'recipe name', 'recipe instructions');
            $recipe->setUser($user);
            $recipe->setName($data['recipe_name']);
            $recipe->setInstructions($data['instructions']);
            $existingRecipe = $this->entityManager->getRepository(Recipe::class)->findOneBy(['name' => $data['recipe_name'], 'user' => $user]);
            if ($existingRecipe) {
                return new JsonResponse(['status' => 'error', 'message' => 'Recipe name already exists for this user.'], 400);
            }
            $this->entityManager->persist($recipe);
            $this->entityManager->flush();
            return new JsonResponse($recipe->toArray(), 201);
        } catch (Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function editRecipe(ServerRequestInterface $request): ResponseInterface {
        try {
            $data = $request->getParsedBody();
            $recipe = $this->entityManager->find(Recipe::class, $data['id']);
            $recipe->setName($data['recipe_name']);
            $recipe->setInstructions($data['instructions']);
            $this->entityManager->flush();
            return new JsonResponse($recipe->toArray(), 200);
        } catch (Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteRecipe(ServerRequestInterface $request): ResponseInterface {
        try {
            $data = $request->getParsedBody();
            $recipe = $this->entityManager->find(Recipe::class, $data['id']);
            $this->entityManager->remove($recipe);
            $this->entityManager->flush();
            return new JsonResponse(['status' => 'success'], 200);
        } catch (Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function checkRecipeItems(ServerRequestInterface $request): ResponseInterface {
        try {
            $data = $request->getParsedBody();
            $recipe = $this->entityManager->find(Recipe::class, $data['recipe_id']);
            $group = $this->entityManager->find(Group::class, $data['group_id']);
            $items = $recipe->getItems();
            $groupItems = $group->getItems();
            $result = [];
            foreach ($items as $item) {
                $groupItem = $groupItems->filter(function($groupItem) use ($item) {
                    return $groupItem->getName() === $item->getName();
                })->first();
                if ($groupItem) {
                    $result[] = [
                        'item' => $item->getName(),
                        'quantity' => $groupItem->getPantryQuantity(),
                        'exists' => true
                    ];
                } else {
                    $result[] = [
                        'item' => $item->getName(),
                        'exists' => false
                    ];
                }
            }
            return new JsonResponse(['status' => 'success', 'data' => $result], 200);
        } catch (Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}