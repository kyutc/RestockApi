<?php

namespace Restock\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'item', schema: 'restock')]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'items')]
    #[Orm\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false)]
    private Group $group;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $description;

    #[ORM\Column(length: 255)]
    private string $category;

    #[ORM\Column]
    private int $pantry_quantity = 0;

    #[ORM\Column]
    private int $minimum_threshold = 0;

    #[ORM\Column]
    private bool $auto_add_to_shopping_list = false;

    #[ORM\Column]
    private int $shopping_list_quantity = 0;

    #[ORM\Column]
    private bool $dont_add_to_pantry_on_purchase = false;

    public function __construct(Group $group, string $name, string $description = '', string $category = '')
    {
        $this->group = $group;
        $this->name = $name;
        $this->description = $description;
        $this->category = $category;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getPantryQuantity(): int
    {
        return $this->pantry_quantity;
    }

    public function setPantryQuantity(int $pantry_quantity): self
    {
        $this->pantry_quantity = $pantry_quantity;
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

    public function getShoppingListQuantity(): int
    {
        return $this->shopping_list_quantity;
    }

    public function setShoppingListQuantity(int $shopping_list_quantity): self
    {
        $this->shopping_list_quantity = $shopping_list_quantity;
        return $this;
    }

    public function isDontAddToPantryOnPurchase(): bool
    {
        return $this->dont_add_to_pantry_on_purchase;
    }

    public function setDontAddToPantryOnPurchase(bool $dont_add_to_pantry_on_purchase): self
    {
        $this->dont_add_to_pantry_on_purchase = $dont_add_to_pantry_on_purchase;
        return $this;
    }

    public function __toString(): string
    {
        // Convert the item to an associative array
        $itemData = [
            'group_id' => $this->getGroup()->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'category' => $this->getCategory(),
            'pantry_quantity' => $this->getPantryQuantity(),
            'minimum_threshold' => $this->getMinimumThreshold(),
            'auto_add_to_shopping_list' => $this->isAutoAddToShoppingList(),
            'shopping_list_quantity' => $this->getShoppingListQuantity(),
            'auto_add_to_pantry' => $this->isDontAddToPantryOnPurchase(),
        ];

        return json_encode($itemData);
    }


}