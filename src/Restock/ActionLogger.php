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
        return $this->createActionLog('User ' . $this->user->getName() . ' added to group');
    }

    public function logUserRemovedFromGroup(): ActionLog {
        return $this->createActionLog('User ' . $this->user->getName() . ' removed from group');
    }

    public function logUserLeftGroup(): ActionLog {
        return $this->createActionLog('User ' . $this->user->getName() . ' left group');
    }

    public function logUserRoleChanged(): ActionLog {
        return $this->createActionLog('User ' . $this->user->getName() . ' role changed');
    }

    public function logUsernameChanged(): ActionLog {
        return $this->createActionLog('User ' . $this->user->getName() . ' name changed', false);
    }

    public function logItemAddedToPantry(Item $item): ActionLog {
        return $this->createActionLog('Item ' . $item->getName() . ' added to pantry');
    }

    public function logItemRemovedFromPantry(Item $item): ActionLog {
        return $this->createActionLog('Item ' . $item->getName() . ' removed from pantry');
    }

    public function logItemAddedToShoppingList(Item $item): ActionLog {
        return $this->createActionLog('Item ' . $item->getName() . ' added to shopping list');
    }

    public function logItemRemovedFromShoppingList(Item $item): ActionLog {
        return $this->createActionLog('Item ' . $item->getName() . ' removed from shopping list');
    }

    public function logGroupNameChanged(): ActionLog {
        return $this->createActionLog('Group ' . $this->group->getName() . ' name changed', false);
    }

    public function logGroupCreated(): ActionLog {
        return $this->createActionLog('Group ' . $this->group->getName() . ' created', false);
    }

    public function logGroupUpdated(): ActionLog {
        return $this->createActionLog('Group ' . $this->group->getName() . ' updated', false);
    }

    public function logItemCreated(Item $item): ActionLog {
        return $this->createActionLog('Item ' . $item->getName() . ' created');
    }

    public function logItemUpdated(Item $item): ActionLog {
        return $this->createActionLog('Item ' . $item->getName() . ' updated');
    }

    public function logItemDeleted(Item $item): ActionLog {
        return $this->createActionLog('Item ' . $item->getName() . ' deleted');
    }

    private function createActionLog(string $logMessage, bool $includeGroupName = true): ActionLog {
        if ($includeGroupName) {
            $logMessage .= ' in group ' . $this->group->getName();
        }
        $actionLog = new ActionLog($this->group, $logMessage);
        $this->entityManager->persist($actionLog);
        $this->entityManager->flush();
        return $actionLog;
    }
}