<?php
session_start();
require __DIR__ . '/../services/db-connect.php';
require __DIR__ . '/../services/auth-register.php';

//bypass if logged in
if (isset($_SESSION['uid'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$form  = ['fname' => '', 'lname' => '', 'email' => '', 'company' => '', 'com_role' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $_) {
        $form[$key] = trim($_POST[$key] ?? '');
    }

    $error = register_user($pdo, $_POST);

    if ($error === '') {
        header('Location: login.php?created=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>PunchDash - Create Account</title>
</head>
<body>
	<h1>Create Account</h1>

	<?php if ($error !== ''): ?>
	<p style="color:#b00020;"><?= htmlspecialchars($error) ?></p>
	<?php endif; ?>

	<form method="post" action="create-account.php">
		<label>First Name
			<input type="text" name="fname" value="<?= htmlspecialchars($form['fname']) ?>" required>
		</label><br>
		
		<label>Last Name
			<input type="text" name="lname" value="<?= htmlspecialchars($form['lname']) ?>" required>
		</label><br>
		
		<label>Email
			<input type="email" name="email" value="<?= htmlspecialchars($form['email']) ?>" required>
		</label><br>
		
		<label>Password (at least 8 characters)
			<input type="password" name="password" id="reg-password" minlength="8" required>
			<button type="button" onclick="togglePassword('reg-password', this)">Show</button>
		</label><br>
		
		<label>Confirm Password
			<input type="password" name="confirm" id="reg-confirm" minlength="8" required>
			<button type="button" onclick="togglePassword('reg-confirm', this)">Show</button>
		</label><br>
		
		<label>Company
			<input type="text" name="company" value="<?= htmlspecialchars($form['company']) ?>" required>
		</label><br>
		
		<label>Role in Company
			<input type="text" name="com_role" value="<?= htmlspecialchars($form['com_role']) ?>" required>
		</label><br>
		
		<button type="submit">Create Account</button>
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

	<p><a href="login.php">Back to Log In</a></p>
</body>
</html>
