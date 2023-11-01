<?php

namespace Restock\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'session', schema: 'restock')]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'session')]
    #[ORM\JoinColumn(name: 'user_id',referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column(length: 100)]
    private string $token;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $create_date;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $last_used_date;

    /**
     * @param User $user
     * @param string $token
     * @param \DateTimeImmutable $create_date
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->token = base64_encode(random_bytes(32));
        $this->create_date = new \DateTimeImmutable('now');
        $this->last_used_date = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCreateDate(): \DateTimeImmutable
    {
        return $this->create_date;
    }

    public function getLastUsedDate(): \DateTimeImmutable
    {
        return $this->last_used_date;
    }

    public function setLastUsedDate(): self
    {
        $this->last_used_date = new \DateTimeImmutable('now'); //Creates a new DateTimeImmutable object to replace old one
        return $this;
    }

}