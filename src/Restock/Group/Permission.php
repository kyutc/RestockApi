<?php

declare(strict_types=1);

namespace Restock\Group;

class Permission
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function canCreateGroup(int $user_id): bool
    {
        // TODO: As of now, there's no condition to disable creating groups
        return true;
    }

    private function getGroupRole(int $user_id, int $group_id): string
    {
        $query = $this->db->prepare(
            "SELECT `role` FROM `groupmember` " .
            "WHERE `groupmember`.`user_id` = ? AND `group`.`group_id` = ?"
        );
        $query->execute([$user_id, $group_id]);

        if ($query->rowCount() == 0) {
            return '';
        }

        return $query->fetch(\PDO::FETCH_ASSOC)['role'];
    }

    public function isGroupMember(int $user_id, int $group_id): bool
    {
        $query = $this->db->prepare(
            "SELECT `role` FROM `groupmember` " .
            "WHERE `groupmember`.`user_id` = ? AND `group`.`group_id` = ?"
        );
        $query->execute([$user_id, $group_id]);

        if ($query->rowCount() == 0) {
            return false;
        }

        return true;
    }

    // TODO: These permissions are role-based and roles are static. This is not a good way to do permissions if the goal is something more complex.
    public function canEditGroup(int $user_id, int $group_id): bool
    {
        return $this->getGroupRole($user_id, $group_id) == 'owner';
    }

    public function canDeleteGroup(int $user_id, int $group_id): bool
    {
        return $this->getGroupRole($user_id, $group_id) == 'owner';
    }

    public function canInviteGroupMember(int $user_id, int $group_id): bool
    {
        return $this->getGroupRole($user_id, $group_id) == 'owner';
    }

    public function canJoinGroup(int $user_id, int $group_id): bool
    {
        throw new \Exception("Not implemented");
    }

    public function canAddItem(int $user_id, int $group_id): bool
    {
        return in_array($this->getGroupRole($user_id, $group_id), ['owner', 'editor']);
    }

    public function canEditItem(int $user_id, int $group_id): bool
    {
        return in_array($this->getGroupRole($user_id, $group_id), ['owner', 'editor']);
    }

    public function canDeleteItem(int $user_id, int $group_id): bool
    {
        return in_array($this->getGroupRole($user_id, $group_id), ['owner', 'editor']);
    }

    public function canAddPantryItem(int $user_id, int $group_id): bool
    {
        return in_array($this->getGroupRole($user_id, $group_id), ['owner', 'editor']);
    }

    public function canEditPantryItem(int $user_id, int $group_id): bool
    {
        return in_array($this->getGroupRole($user_id, $group_id), ['owner', 'editor']);
    }

    public function canDeletePantryItem(int $user_id, int $group_id): bool
    {
        return in_array($this->getGroupRole($user_id, $group_id), ['owner', 'editor']);
    }

    public function canAddShoppingListItem(int $user_id, int $group_id): bool
    {
        return in_array($this->getGroupRole($user_id, $group_id), ['owner', 'editor']);
    }

    public function canEditShoppingListItem(int $user_id, int $group_id): bool
    {
        return in_array($this->getGroupRole($user_id, $group_id), ['owner', 'editor']);
    }

    public function canDeleteShoppingListItem(int $user_id, int $group_id): bool
    {
        return in_array($this->getGroupRole($user_id, $group_id), ['owner', 'editor']);
    }
}