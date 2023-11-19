<?php

namespace Restock\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types as Types;

#[ORM\Entity]
#[ORM\Table(name: 'recipe', schema: 'restock')]
class Recipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'recipes')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: Types::TEXT)]
    private string $ingredients;

    #[ORM\Column(type: Types::TEXT)]
    private string $instructions;

    public function __construct(User $user, string $name, string $ingredients, string $instructions)
    {
        $this->user = $user;
        $this->name = $name;
        $this->ingredients = $ingredients;
        $this->instructions = $instructions;
    }

    public function toArray(): array {
        return [
            "id" => $this->getId(),
            "name" => $this->getName(),
            "ingredients" => $this->getIngredients(),
            "instructions" => $this->getInstructions()
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
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

    public function getIngredients(): string
    {
        return $this->ingredients;
    }

    public function setIngredients(string $ingredients): void
    {
        $this->ingredients = $ingredients;
    }

    public function getInstructions(): string
    {
        return $this->instructions;
    }

    public function setInstructions(string $instructions): void
    {
        $this->instructions = $instructions;
    }

}