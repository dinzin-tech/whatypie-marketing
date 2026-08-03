<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class User extends Model
{
    public int $id;
    public string $username;
    public string $email;
    public string $password;
    public string $role = 'admin';
    public string $created_at;
    public string $updated_at;

    public function __construct()
    {
        $this->table = 'users';
        parent::__construct();
    }

    public static function findByEmail(string $email): ?static
    {
        return static::findOneBy(['email' => $email]);
    }

    public static function findByUsername(string $username): ?static
    {
        return static::findOneBy(['username' => $username]);
    }

    public function verifyPassword(string $plain): bool
    {
        return password_verify($plain, $this->password);
    }

    public function setPassword(string $plain): void
    {
        $this->password = password_hash($plain, PASSWORD_BCRYPT);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
