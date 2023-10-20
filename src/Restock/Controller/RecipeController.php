<?php

declare(strict_types=1);

namespace Restock\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Restock\Entity\Recipe;
use Restock\Entity\User;
use Doctrine\ORM\EntityManager;
Use Exception;

class RecipeController {
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function createRecipe(ServerRequestInterface $request): ResponseInterface {
        try {
            $data = $request->getParsedBody();
            $recipe = new Recipe();
            $user = $this->entityManager->find(User::class, $data['user_id']);
            $recipe->setUser($user);
            $recipe->setName($data['recipe_name']);
            $recipe->setInstructions($data['instructions']);
            $this->entityManager->persist($recipe);
            $this->entityManager->flush();
            return new JsonResponse(['status' => 'success'], 201);
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
            return new JsonResponse(['status' => 'success'], 200);
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
}