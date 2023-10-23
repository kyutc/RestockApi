<?php

namespace Restock\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'group', schema: 'restock')]
class Group
{
    #[Orm\Id]
    #[Orm\GeneratedValue]
    #[Orm\Column]
    private ?int $id;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\OneToMany(mappedBy: 'group', targetEntity: GroupMember::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $group_members;

    #[ORM\OneToMany(mappedBy: 'group', targetEntity: Item::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\OneToMany(mappedBy: 'group', targetEntity: ActionLog::class, cascade: ['remove'])]
    private Collection $history;

    public function __construct(string $name, User $owner)
    {
        $this->name = $name;
        $this->group_members = new ArrayCollection();
        $this->addGroupMember($owner, GroupMember::OWNER);
        $this->items = new ArrayCollection();
        $this->history = new ArrayCollection();
    }

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

    public function addGroupMember(User $user, string $role = GroupMember::MEMBER): self
    {
        $this->group_members->add(new GroupMember($this, $user, $role));
        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getHistory(): Collection
    {
        return $this->history;
    }
}