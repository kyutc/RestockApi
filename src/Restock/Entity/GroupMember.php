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
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false)]
    private Group $group;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
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

    public function __toString(): string
    {
        $group_member_details = [$this->id => [
                'group_id' => $this->group->getId(),
                'user_id' => $this->user->getId(),
                'role' => $this->role
            ]
        ];

        return json_encode($group_member_details);
    }

    public function getId(): ?int
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

    public function isHigherRoleThan(GroupMember $group_member): bool {
        $quantifyRole = function (string $role): int {
            // Lower indices are superior to greater indices
            $roles = [
                self::OWNER,
                self::ADMIN,
                self::MEMBER
            ];
            return array_search($role, $roles);
        };
        $this_member = $quantifyRole($this->getRole());
        $that_member = $quantifyRole($group_member->getRole());
        return $this_member < $that_member;
    }

    public function toArray(): array {
        return [
            "id" => $this->getId(),
            "group_id" => $this->getGroup()->getId(),
            "user_id" => $this->getUser()->getId(),
            "name" => $this->getUser()->getName(),
            "role" => $this->getRole()
        ];
    }
}