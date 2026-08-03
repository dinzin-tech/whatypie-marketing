<?php

namespace App\Commands;

use App\Models\User;

class CreateUserCommand
{
    public function execute(array $arguments): void
    {
        if (count($arguments) < 3) {
            echo "Usage: php bin/console createuser <username> <email> <password> [role]\n";
            return;
        }

        [$username, $email, $password] = $arguments;
        $role = $arguments[3] ?? 'admin';

        if (User::findByEmail($email)) {
            echo "Error: A user with email '{$email}' already exists.\n";
            return;
        }

        if (User::findByUsername($username)) {
            echo "Error: A user with username '{$username}' already exists.\n";
            return;
        }

        $user = new User();
        $user->username = $username;
        $user->email = $email;
        $user->role = $role;
        $user->setPassword($password);
        $user->save();

        echo "User '{$username}' ({$email}) created successfully with role '{$role}'.\n";
    }
}
