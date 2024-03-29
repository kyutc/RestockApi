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

    #[ORM\OneToMany(mappedBy: 'group', targetEntity: GroupMember::class, cascade: [
        'persist',
        'remove'
    ], orphanRemoval: true)]
    private Collection $group_members;

    #[ORM\OneToMany(mappedBy: 'group', targetEntity: Invite::class, cascade: [
        'persist',
        'remove'
    ], orphanRemoval: true)]
    private Collection $invites;

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

    public function __toString(): string
    {
        $group_details = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'group_members' => implode(
                ',',
                array_map(fn(GroupMember $gm) => strval($gm), $this->getGroupMembers()->toArray())
            ),
        ];
        $items = $this->getItems()->toArray();
        if (count($items)) {
            $group_details['items'] = implode(',', array_map(fn(Item $i) => strval($i), $items));
        }


        return json_encode($group_details);
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

    public function getInvites(): Collection
    {
        return $this->invites;
    }

    public function createInvite(): Invite
    {
        $invite = new Invite($this);
        $this->invites->add($invite);
        return $invite;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getHistory(): Collection
    {
        return $this->history;
    }

    public function createItem(
        string $itemName,
        string $description = '',
        string $category = 'default;#ffffff',
        int $pantry_quantity = 0,
        int $minimum_threshold = 0,
        bool $auto_add_to_shopping_list = true,
        int $shopping_list_quantity = 0,
        bool $add_to_pantry_on_purchase = false
    ): Item {
        $newItem = new Item(
            $this,
            $itemName,
            $description,
            $category,
            $pantry_quantity,
            $minimum_threshold,
            $auto_add_to_shopping_list,
            $shopping_list_quantity,
            $add_to_pantry_on_purchase
        );
        $this->items->add($newItem);
        return $newItem;
    }

    public function removeItem(Item $item): self
    {
        $this->items->removeElement($item);
        return $this;
    }

    public function toArray(): array {
        return [
            "id" => $this->getId(),
            "name" => $this->getName()
        ];
    }

}