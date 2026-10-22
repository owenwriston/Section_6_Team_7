<?php
function register_user(PDO $pdo, array $in): string
{
    $fname    = trim($in['fname'] ?? '');
    $lname    = trim($in['lname'] ?? '');
    $email    = trim($in['email'] ?? '');
    $company  = trim($in['company'] ?? '');
    $comRole  = trim($in['com_role'] ?? '');
    $password = $in['password'] ?? '';
    $confirm  = $in['confirm'] ?? '';


    if ($fname === '' || $lname === '' || $email === '' ||
        $company === '' || $comRole === '' || $password === '') {
        return 'All fields are required.';
    }

    // Format checks
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        return 'Passwords do not match.';
    }

    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return 'An account with that email already exists.';
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, passhash, fname, lname, company, `com-role`)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$email, $hash, $fname, $lname, $company, $comRole]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return 'An account with that email already exists.';
        }
        throw $e;
    }

    return '';
}
