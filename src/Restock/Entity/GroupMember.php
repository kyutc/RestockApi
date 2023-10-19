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

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'group_members')]
    private Group $group;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id')]
    private User $user;


    /**
     * Consider whether it's worth having enums.
     * https://www.doctrine-project.org/projects/doctrine-orm/en/2.16/cookbook/mysql-enums.html
     * MariaDB has the option to add enum values without rewriting the table since 10.3.7
     * https://mariadb.com/kb/en/innodb-online-ddl-operations-with-the-instant-alter-algorithm/#adding-a-new-enum-option
     */
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

    public function getId(): int
    {
        return $this->id;
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