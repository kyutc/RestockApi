<?php declare(strict_types=1);

namespace Restock\Db;

class Register
{
    // This class should probably be modified to include other uses
    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    private function CreatePasswordHash(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    private function ValidatePasswordHash(string $password, string $password_hash): bool {
        return password_verify($password, $password_hash);
    }

    private function CreateUserApiToken(string $user_id): string {
        return base64_encode(random_bytes(32));
    }

    public function ValidateUserApiToken(string $token): bool {
        $query = $this->db->prepare(
            'SELECT COUNT(*) AS `count` FROM `apiauth` WHERE `token` = ?');
        $query->execute([$token]);
        $result = $query->fetch(\PDO::FETCH_ASSOC)['count'];
        return $result == 1;
    }

    // Other attributes may be added later, ex. email, if desired.
    public function CreateAccount(string $username, string $password): void {
        $query = $this->db->prepare('INSERT INTO `user` (`name`, `password`) VALUES (?, ?)');
        $password_hash = $this->CreatePasswordHash($password);
        $query->execute([$username, $password_hash]);
    }

    public function Login(string $username, string $password, string &$token): bool {
        $query = $this->db->prepare('SELECT `id`, `password` FROM `user` WHERE `name` = ?');
        $query->execute([$username]);
        $result = $query->fetch(\PDO::FETCH_ASSOC);
        $user_id = (string)$result['id'] ?? '';
        $password_hash = $result['password'] ?? '';

        if ($this->ValidatePasswordHash($password, $password_hash)) {
            $token = $this->CreateUserApiToken($user_id);
            $query = $this->db->prepare(
                'INSERT INTO `apiauth` (`user_id`, `token`, `create_date`, `last_use_date`) VALUES (?, ?, NOW(), NOW())');
            $query->execute([$user_id, $token]);
            return true;
        }

        return false;
    }

    public function CheckUsernameAvailability(string $username): bool {
        $query = $this->db->prepare('SELECT COUNT(*) AS `count` FROM `user` WHERE `name` = ?');
        $query->execute([$username]);
        $result = $query->fetch(\PDO::FETCH_ASSOC)['count'];
        return $result == 0;
    }
}