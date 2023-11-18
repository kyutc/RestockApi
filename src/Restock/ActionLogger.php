<?php

declare(strict_types=1);

namespace Restock;

use Restock\Entity\ActionLog;
use Restock\Entity\Group;
use Doctrine\ORM\EntityManager;

class ActionLogger {
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function createActionLog(Group $group, string $logMessage): ActionLog {
        $actionLog = new ActionLog($group, $logMessage);
        $this->entityManager->persist($actionLog);
        $this->entityManager->flush();
        return $actionLog;
    }
}