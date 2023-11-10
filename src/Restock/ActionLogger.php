<?php

declare(strict_types=1);

namespace Restock;

use Restock\Entity\ActionLog;
use Restock\Entity\Group;
use Restock\Entity\User;
use Restock\Entity\Item;
use Doctrine\ORM\EntityManager;

class ActionLogger {
    private EntityManager $entityManager;
    private User $user;
    private Group $group;

    public function __construct(EntityManager $entityManager, User $user, Group $group) {
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->group = $group;
    }

    public function logUserAddedToGroup(): ActionLog {
        return $this->createActionLog('User ' . $this->user->getName() . ' added to group ' . $this->group->getName());
    }

    public function logUserRemovedFromGroup(): ActionLog {
        return $this->createActionLog('User ' . $this->user->getName() . ' removed from group ' . $this->group->getName());
    }

    public function logUserLeftGroup(): ActionLog {
        return $this->createActionLog('User ' . $this->user->getName() . ' left group ' . $this->group->getName());
    }

    public function logUserRoleChanged(): ActionLog {
        return $this->createActionLog('User ' . $this->user->getName() . ' role changed in group ' . $this->group->getName());
    }

    public function logUsernameChanged(): ActionLog {
        return $this->createActionLog('User ' . $this->user->getName() . ' name changed');
    }

    public function logItemAddedToPantry(Item $item): ActionLog {
        return $this->createActionLog('Item ' . $item->getName() . ' added to pantry in group ' . $this->group->getName());
    }

    public function logItemRemovedFromPantry(Item $item): ActionLog {
        return $this->createActionLog('Item ' . $item->getName() . ' removed from pantry in group ' . $this->group->getName());
    }

    public function logItemAddedToShoppingList(Item $item): ActionLog {
        return $this->createActionLog('Item ' . $item->getName() . ' added to shopping list in group ' . $this->group->getName());
    }

    public function logItemRemovedFromShoppingList(Item $item): ActionLog {
        return $this->createActionLog('Item ' . $item->getName() . ' removed from shopping list in group ' . $this->group->getName());
    }

    public function logGroupNameChanged(): ActionLog {
        return $this->createActionLog('Group ' . $this->group->getName() . ' name changed');
    }

    private function createActionLog(string $logMessage): ActionLog {
        $actionLog = new ActionLog($this->group, $logMessage);
        $this->entityManager->persist($actionLog);
        $this->entityManager->flush();
        return $actionLog;
    }
}