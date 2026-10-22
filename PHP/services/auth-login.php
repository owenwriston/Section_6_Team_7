<?php
//checks login info with db
function login_user(PDO $pdo, string $email, string $password): ?array
{
    $stmt = $pdo->prepare(
        'SELECT uid, passhash, fname, lname, permissions FROM users WHERE email = ?'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['passhash'])) {
        return $user;
    }
    return null;
}
