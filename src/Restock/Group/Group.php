<?php

declare(strict_types=1);

namespace Restock\Group;

class Group
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function CreateGroup(string $name): string
    {
        $query = $this->db->prepare('INSERT INTO `group` (`name`) VALUES (?)');
        $query->execute([$name]);
        return $this->db->lastInsertId('id');
    }

    public function SetGroupMemberRole(int $group_id, string $role): void
    {
        throw new \Exception("Not implemented");
    }

    public function DeleteGroup(int $group_id, string $token): bool
    {
        throw new \Exception("Not implemented");
        $query = $this->db->prepare(
            ''
        );
        $query->execute([$group_id, $token]);
        return $query->rowCount() > 0;
    }

    public function CheckGroupNameAvailability(string $name): bool
    {
        $query = $this->db->prepare('SELECT COUNT(*) AS `count` FROM `group` WHERE `name` = ?');
        $query->execute([$name]);
        $result = $query->fetch(\PDO::FETCH_ASSOC)['count'];
        return $result == 0;
    }
}