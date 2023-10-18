<?php

namespace Restock\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'group', schema: 'restock')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\OneToMany(targetEntity: GroupMember::class, mappedBy: 'group', cascade: ['persist', 'remove'])]
    private Collection $group_members;

    #[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'group', cascade: ['remove'])]
    private Collection $items;

    #[ORM\OneToMany(targetEntity: ActionLog::class, mappedBy: 'group', cascade: ['remove'])]
    private Collection $history;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getGroupMembers(): Collection
    {
        return $this->group_members;
    }

    public function addGroupMember(GroupMember $group_member): self
    {
        if (! $this->group_members->contains($group_member) )
        {
            $this->group_members->add($group_member);
        }
        return $this;
    }
}