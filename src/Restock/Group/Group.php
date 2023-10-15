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

    public function SetGroupPermission(int $group_id, string $permission): void
    {

    }

    public function DeleteGroup(int $group_id, string $token): bool
    {
        $query = $this->db->prepare(
            'DELETE `group` FROM `group` ' .
            'JOIN `apiauth` ON `apiauth`.`user_id` = `user`.`id` ' .
            'WHERE `user`.`id` = ? AND `token` = ?'
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