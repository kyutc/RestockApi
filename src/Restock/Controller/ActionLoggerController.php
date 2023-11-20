<?php

declare(strict_types=1);

namespace Restock\Controller;

use DateTimeImmutable;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Restock\Entity\ActionLog;
use Doctrine\ORM\EntityManager;
use Exception;
use Restock\Entity\User;
use Restock\PResponse;

class ActionLoggerController
{
    private EntityManager $entityManager;
    private User $user;

    public function __construct(EntityManager $entityManager, User $user)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    public function getActionLog(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $id = $request->getAttribute('id');
            $actionLog = $this->entityManager->find(ActionLog::class, $id);
            if ($actionLog) {
                return PResponse::ok($actionLog->toArray());
            } else {
                return PResponse::notFound();
            }
        } catch (Exception $e) {
            return PResponse::serverErr('Failed to update database');
        }
    }

    public function getActionLogsAfterDatetime(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $datetime = $request->getQueryParams()['datetime'] ?? '';
            $datetime = new DateTimeImmutable($datetime);
            $query = $this->entityManager->createQuery(
                'SELECT a FROM Restock\Entity\ActionLog a WHERE a.timestamp > :datetime'
            );
            $query->setParameter('datetime', $datetime);
            $actionLogs = $query->getResult();
            $response = [];
            foreach ($actionLogs as $actionLog) {
                $response[] = [
                    'log_message' => $actionLog->getLogMessage(),
                    'group' => $actionLog->getGroup()->getId(),
                    'timestamp' => $actionLog->getTimestamp()->format('Y-m-d H:i:s')
                ];
            }
            return new JsonResponse($response, 200);
        } catch (Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}