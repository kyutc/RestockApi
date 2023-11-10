<?php

namespace Restock\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManager;

#[ORM\Entity]
#[ORM\Table(name: 'action_log', schema: 'restock')]
class ActionLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'history')]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false)]
    private Group $group;

    #[ORM\Column(type: Types::TEXT)]
    private string $log_message;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $timestamp;

    public function __construct(Group $group, string $log_message)
    {
        $this->group = $group;
        $this->log_message = $log_message;
        $this->timestamp = new DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function setGroup(Group $group): self
    {
        $this->group = $group;
        return $this;
    }

    public function getLogMessage(): string
    {
        return $this->log_message;
    }

    public function setLogMessage(string $log_message): self
    {
        $this->log_message = $log_message;
        return $this;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function setTimestamp(DateTimeImmutable $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }
}

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
        $actionLog->setTimestamp(new DateTimeImmutable('now'));
        $this->entityManager->persist($actionLog);
        $this->entityManager->flush();
        return $actionLog;
    }
}