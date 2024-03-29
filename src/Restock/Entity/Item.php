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
    private int $pantry_quantity;

    #[ORM\Column]
    private int $minimum_threshold;

    #[ORM\Column]
    private bool $auto_add_to_shopping_list;

    #[ORM\Column]
    private int $shopping_list_quantity;

    #[ORM\Column]
    private bool $add_to_pantry_on_purchase;

    public function __construct(
        Group $group,
        string $name,
        string $description = '',
        string $category = '',
        int $pantry_quantity = 0,
        int $minimum_threshold = 0,
        bool $auto_add_to_shopping_list = true,
        int $shopping_list_quantity = 0,
        bool $add_to_pantry_on_purchase = false
    ) {
        $this->group = $group;
        $this->name = $name;
        $this->description = $description;
        $this->category = $category;
        $this->pantry_quantity = $pantry_quantity;
        $this->minimum_threshold = $minimum_threshold;
        $this->auto_add_to_shopping_list = $auto_add_to_shopping_list;
        $this->shopping_list_quantity = $shopping_list_quantity;
        $this->add_to_pantry_on_purchase = $add_to_pantry_on_purchase;
    }

    public function toArray(): array {
        return [
            "id" => $this->getId(),
            "group_id" => $this->getGroup()->getId(),
            "name" => $this->getName(),
            "description" => $this->getDescription(),
            "category" => $this->getCategory(),
            "pantry_quantity" => $this->getPantryQuantity(),
            "minimum_threshold" => $this->getMinimumThreshold(),
            "auto_add_to_shopping_list" => $this->isAutoAddToShoppingList(),
            "shopping_list_quantity" => $this->getShoppingListQuantity(),
            "add_to_pantry_on_purchase" => $this->isAddToPantryOnPurchase()
        ];
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

    public function isAddToPantryOnPurchase(): bool
    {
        return $this->add_to_pantry_on_purchase;
    }

    public function setAddToPantryOnPurchase(bool $add_to_pantry_on_purchase): self
    {
        $this->add_to_pantry_on_purchase = $add_to_pantry_on_purchase;
        return $this;
    }

    public function __toString(): string
    {
        // Convert the item to an associative array
        $itemData = [
            'id' => $this->getId(),
            'group_id' => $this->getGroup()->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'category' => $this->getCategory(),
            'pantry_quantity' => $this->getPantryQuantity(),
            'minimum_threshold' => $this->getMinimumThreshold(),
            'auto_add_to_shopping_list' => $this->isAutoAddToShoppingList(),
            'shopping_list_quantity' => $this->getShoppingListQuantity(),
            'auto_add_to_pantry' => $this->isAddToPantryOnPurchase(),
        ];

        return json_encode($itemData);
    }


}