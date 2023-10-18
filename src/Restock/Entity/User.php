<?php

namespace Restock\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user', schema: 'restock')]
#[ORM\UniqueConstraint(name: 'email', columns: ['email'])]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 100)]
    private string $password;

    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Recipe::class, cascade: ['persist', 'remove'])]
    private Collection $recipes;

    /**
     * @param string $name
     * @param string $password
     * @param string $email
     */
    public function __construct(string $name, string $password, string $email)
    {
        $this->name = $name;
        $this->password = $password; # Todo: hash password
        $this->email = $email;
        $this->recipes = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getPassword(): string
    {
        return $this->password;
    }

    private function createPasswordHash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public function setPassword(string $password): self
    {
        $this->password = $this->createPasswordHash($password);
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getRecipes(): Collection
    {
        return $this->recipes;
    }

    public function addRecipe(Recipe $recipe): self
    {
        if (! $this->recipes->contains($recipe) ) {
            $this->recipes->add($recipe);
            $recipe->setUser($this);
        }
        return $this;
    }

    public function removeRecipe(Recipe $recipe): self
    {
        if ($this->recipes->contains($recipe)) {
            $this->recipes->remove($recipe);
        }
        return $this;
    }
}