<?php

namespace Restock\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user', schema: 'restock')]
class User
{
    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 100)]
    private string $password;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private string $email;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Session::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $sessions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Recipe::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
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
        $this->sessions = new ArrayCollection();
        $this->recipes = new ArrayCollection();
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

    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function createSession(): self
    {
        $this->sessions->add(new Session($this));
        return $this;
    }

    public function hasSession(string $token): bool {
        if ($this->sessions->get($token) ?? false) return true;
        return false;
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