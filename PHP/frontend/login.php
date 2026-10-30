<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../services/db-connect.php';
require __DIR__ . '/../services/auth-login.php';

//redirect if logged in
if (isset($_SESSION['uid'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $user = login_user($pdo, $email, $password);

        if ($user) {
            session_regenerate_id(true);
            $_SESSION['uid'] = $user['uid'];
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PunchDash - Log In</title>
</head>
<body>
    <h1>PunchDash</h1>

    <?php if ($error !== ''): ?>
        <p style="color:#b00020;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="login.php">
        <label>Email
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </label><br>
        <label>Password
            <input type="password" name="password" id="login-password" required>
			<button type="button" onclick="togglePassword('login-password', this)">Show</button>
        </label><br>
        <button type="submit">Log In</button>
    </form>
	
	<script>
	function togglePassword(inputId, btn) {
		const field = document.getElementById(inputId);
		if (field.type === 'password') {
			field.type = 'text';
			btn.textContent = 'Hide';
		} else {
			field.type = 'password';
			btn.textContent = 'Show';
		}
	}
	</script>

    <p><a href="create-account.php">Create Account</a></p>
</body>
</html>
