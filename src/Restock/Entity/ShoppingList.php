<?php

namespace Restock\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'shopping_list', schema: 'restock')]
class ShoppingList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\OneToOne(targetEntity: Item::class)]
    private Item $item;

    #[ORM\Column]
    private int $quantity;

    #[ORM\Column]
    private bool $dont_add_to_pantry;

    public function getId(): int
    {
        return $this->id;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $item): self
    {
        $this->item = $item;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function isDontAddToPantry(): bool
    {
        return $this->dont_add_to_pantry;
    }

    public function setDontAddToPantry(bool $dont_add_to_pantry): self
    {
        $this->dont_add_to_pantry = $dont_add_to_pantry;
        return $this;
    }
}