<?php

declare(strict_types=1);

namespace Restock\Db;

class UserAccount
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    private function CreatePasswordHash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    private function ValidatePasswordHash(string $password, string $password_hash): bool
    {
        return password_verify($password, $password_hash);
    }

    private function CreateUserApiToken(string $user_id): string
    {
        return base64_encode(random_bytes(32));
    }

    public function ValidateUserApiToken(string $token): bool
    {
        $query = $this->db->prepare(
            'SELECT COUNT(*) AS `count` FROM `apiauth` WHERE `token` = ?'
        );
        $query->execute([$token]);
        $result = $query->fetch(\PDO::FETCH_ASSOC)['count'];
        return $result == 1;
    }

    // Other attributes may be added later, ex. email, if desired.
    public function CreateAccount(string $username, string $password): void
    {
        // Note: There is a mild race condition here.
        // If the client checks availability of a username and then creates an account with a name which was taken
        // within that time, this query will fail. Also, it'd be smart to implement proper error reporting in general.
        $query = $this->db->prepare('INSERT INTO `user` (`name`, `password`) VALUES (?, ?)');
        $password_hash = $this->CreatePasswordHash($password);
        $query->execute([$username, $password_hash]);
    }

    public function DeleteAccount(int $user_id, string $token): bool
    {
        // Mixed concerns, this function shouldn't care about auth.
        // Auth should be handled elsewhere before this function gets called.
        // Failure here can be because the user isn't authenticated, the userid is wrong, or the user doesn't have
        // permission to delete the target user. Will need to expand on these later and provide better errors.
        // It could be implied that any failure here means it's a permission issue (with current auth flow)
        $query = $this->db->prepare(
            'DELETE `user` FROM `user` ' .
            'JOIN `apiauth` ON `apiauth`.`user_id` = `user`.`id` ' .
            'WHERE `user`.`id` = ? AND `token` = ?'
        );
        $query->execute([$user_id, $token]);
        return $query->rowCount() > 0;
    }

    public function Login(string $email, string $password, string &$token): bool
    {
        $query = $this->db->prepare('SELECT `id`, `password` FROM `user` WHERE `email` = ?');
        $query->execute([$email]);

        if ($query->rowCount() !== 1) {
            return false;
        }

        $result = $query->fetch(\PDO::FETCH_ASSOC);
        $user_id = (string)$result['id'] ?? '';
        $password_hash = $result['password'] ?? '';

        if ($this->ValidatePasswordHash($password, $password_hash)) {
            $token = $this->CreateUserApiToken($user_id);
            $query = $this->db->prepare(
                'INSERT INTO `apiauth` (`user_id`, `token`, `create_date`, `last_use_date`)' .
                'VALUES (?, ?, NOW(), NOW())'
            );
            $query->execute([$user_id, $token]);
            return true;
        }

        return false;
    }

    public function Logout(string $token): bool
    {
        $query = $this->db->prepare('DELETE FROM `apiauth` WHERE `token` = ?');
        $query->execute([$token]);
        return $query->rowCount() == 1;
    }

    public function CheckUsernameAvailability(string $username): bool
    {
        $query = $this->db->prepare('SELECT COUNT(*) AS `count` FROM `user` WHERE `name` = ?');
        $query->execute([$username]);
        $result = $query->fetch(\PDO::FETCH_ASSOC)['count'];
        return $result == 0;
    }
}