<?php

namespace Restock\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types as Types;

#[ORM\Entity]
#[ORM\Table(name: 'recipe', schema: 'restock')]
class Recipe
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'recipes')]
    #[ORM\JoinColumn(name: 'user', referencedColumnName: 'email', nullable: false)]
    private User $user;

    #[ORM\Id]
    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: Types::TEXT)]
    private string $instructions;

    public function __construct(User $user, string $name, string $instructions)
    {
        $this->user = $user;
        $this->name = $name;
        $this->instructions = $instructions;
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

    public function getInstructions(): string
    {
        return $this->instructions;
    }

    public function setInstructions(string $instructions): void
    {
        $this->instructions = $instructions;
    }

}