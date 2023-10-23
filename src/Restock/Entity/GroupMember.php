<?php

namespace Restock\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'group_member', schema: 'restock')]
class GroupMember
{
    // New roles must also be added to the switch statement in self::setRole to be used
    const OWNER = 'owner';
    const ADMIN = 'admin';
    const MEMBER = 'member';

    #[Orm\Id]
    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'group_members')]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false)]
    private Group $group;

    #[Orm\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user', referencedColumnName: 'email', nullable: false)]
    private User $user;

    #[ORM\Column]
    private string $role;

    /**
     * @param Group $group
     * @param User $user
     * @param string $role
     */
    public function __construct(Group $group, User $user, string $role = self::MEMBER)
    {
        $this->group = $group;
        $this->user = $user;
        $this->setRole($role);
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        switch($role) {
            case self::OWNER:
            case self::ADMIN:
            case self::MEMBER:
                break;
            default:
                throw new \InvalidArgumentException("Invalid status");
        }
        $this->role = $role;
        return $this;
    }
}