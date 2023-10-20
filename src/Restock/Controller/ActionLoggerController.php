<?php

declare(strict_types=1);

namespace Restock\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Restock\Entity\ActionLog;
use Doctrine\ORM\EntityManager;
Use Exception;
class ActionLoggerController {
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function createActionLog(ServerRequestInterface $request): ResponseInterface {
        try {
            $data = $request->getParsedBody();
            $actionLog = new ActionLog();
            $actionLog->setLogMessage($data['log_message']);
            $actionLog->setGroup($data['group']);
            $actionLog->setTimestamp(new \DateTimeImmutable());
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
}
