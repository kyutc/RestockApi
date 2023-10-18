<?php

namespace Restock\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pantry', schema: 'restock')]
class Pantry
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
    private int $minimum_threshold;

    #[ORM\Column]
    private bool $auto_add_to_shopping_list;

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

    public function getMinimumThreshold(): int
    {
        return $this->minimum_threshold;
    }

    public function setMinimumThreshold(int $minimum_threshold): self
    {
        $this->minimum_threshold = $minimum_threshold;
        return $this;
    }

    public function isAutoAddToShoppingList(): bool
    {
        return $this->auto_add_to_shopping_list;
    }

    public function setAutoAddToShoppingList(bool $auto_add_to_shopping_list): self
    {
        $this->auto_add_to_shopping_list = $auto_add_to_shopping_list;
        return $this;
    }
}