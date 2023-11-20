<?php

namespace Restock\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

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

    public function toArray(): array {
        return [
            "id" => $this->getId(),
            "group_id" => $this->getGroup()->getId(),
            "log_message" => $this->getLogMessage(),
            "timestamp" => $this->getTimestamp()->format('U')
        ];
    }
}