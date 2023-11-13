<?php

namespace Restock\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invite', schema: 'restock')]
class Invite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'invites')]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false)]
    private Group $group;

    #[ORM\Column(length: 100)]
    private string $code;

    public function __construct(Group $group)
    {
        $this->group = $group;
        $this->code = base64_encode(random_bytes(18));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}