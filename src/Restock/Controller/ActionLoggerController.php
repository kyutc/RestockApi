<?php

declare(strict_types=1);

namespace Restock\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Restock\Entity\ActionLog;
use Doctrine\ORM\EntityManager;
use Restock\Entity\Group;
Use Exception;

class ActionLoggerController {
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function logUserAddedToGroup(ServerRequestInterface $request): ResponseInterface {
        return $this->createActionLog($request, 'User added to group');
    }

    public function logUserRemovedFromGroup(ServerRequestInterface $request): ResponseInterface {
        return $this->createActionLog($request, 'User removed from group');
    }

    public function logUserLeftGroup(ServerRequestInterface $request): ResponseInterface {
        return $this->createActionLog($request, 'User left group');
    }

    public function logUserRoleChanged(ServerRequestInterface $request): ResponseInterface {
        return $this->createActionLog($request, 'User role changed');
    }

    public function logUsernameChanged(ServerRequestInterface $request): ResponseInterface {
        return $this->createActionLog($request, 'Username changed');
    }

    public function logItemAddedToPantry(ServerRequestInterface $request): ResponseInterface {
        return $this->createActionLog($request, 'Item added to pantry');
    }

    public function logItemRemovedFromPantry(ServerRequestInterface $request): ResponseInterface {
        return $this->createActionLog($request, 'Item removed from pantry');
    }

    public function logItemAddedToShoppingList(ServerRequestInterface $request): ResponseInterface {
        return $this->createActionLog($request, 'Item added to shopping list');
    }

    public function logItemRemovedFromShoppingList(ServerRequestInterface $request): ResponseInterface {
        return $this->createActionLog($request, 'Item removed from shopping list');
    }

    public function logGroupNameChanged(ServerRequestInterface $request): ResponseInterface {
        return $this->createActionLog($request, 'Group name changed');
    }

    public function createActionLog(ServerRequestInterface $request, string $logMessage): ResponseInterface {
        try {
            $data = $request->getParsedBody();
            $group = $this->entityManager->find(Group::class, $data['group_id']);
            $actionLog = new ActionLog($group, $logMessage);
            $this->entityManager->persist($actionLog);
            $this->entityManager->flush();
            return new JsonResponse(['status' => 'success'], 201);
        } catch (Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getActionLog(ServerRequestInterface $request): ResponseInterface {
        try {
            $id = $request->getAttribute('id');
            $actionLog = $this->entityManager->find(ActionLog::class, $id);
            if ($actionLog) {
                return new JsonResponse(['log_message' => $actionLog->getLogMessage(), 'group' => $actionLog->getGroup()->getId(), 'timestamp' => $actionLog->getTimestamp()->format('Y-m-d H:i:s')], 200);
            } else {
                return new JsonResponse(['status' => 'error', 'message' => 'Log not found'], 404);
            }
        } catch (Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getActionLogsAfterDatetime(ServerRequestInterface $request): ResponseInterface {
        try {
            $datetime = $request->getQueryParams()['datetime'] ?? '';
            $datetime = new \DateTimeImmutable($datetime);
            $query = $this->entityManager->createQuery('SELECT a FROM Restock\Entity\ActionLog a WHERE a.timestamp > :datetime');
            $query->setParameter('datetime', $datetime);
            $actionLogs = $query->getResult();
            $response = [];
            foreach ($actionLogs as $actionLog) {
                $response[] = ['log_message' => $actionLog->getLogMessage(), 'group' => $actionLog->getGroup()->getId(), 'timestamp' => $actionLog->getTimestamp()->format('Y-m-d H:i:s')];
            }
            return new JsonResponse($response, 200);
        } catch (Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}