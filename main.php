<?php
session_start();

/* ---------- DATABASE ---------- */
$pdo = new PDO(
	'mysql:host=localhost;dbname=punchdash-db;charset=utf8mb4',
	'punchdash_app',
	'a-strong-password',
	[
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]
);

/* ---------- JSON REQUESTS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	header('Content-Type: application/json');
	$input  = json_decode(file_get_contents('php://input'), true) ?? [];
	$action = $input['action'] ?? '';

	if ($action === 'login') {
		$email = trim($input['email'] ?? '');
		$pwd   = $input['pwd'] ?? '';

		if ($email === '' || $pwd === '') {
			echo json_encode(['success' => false, 'error' => 'missing_fields']);
			exit;
		}

		$stmt = $pdo->prepare("SELECT uid, passhash, fname FROM users WHERE email = ?");
		$stmt->execute([$email]);
		$user = $stmt->fetch();

		if (!$user) {
			echo json_encode(['success' => false, 'error' => 'no_account']);
		} elseif (!password_verify($pwd, $user['passhash'])) {
			echo json_encode(['success' => false, 'error' => 'wrong_password']);
		} else {
			session_regenerate_id(true);
			$_SESSION['uid'] = $user['uid'];
			echo json_encode(['success' => true, 'fname' => $user['fname']]);
		}
		exit;
	}

	// Add more actions here later: 'createUser', 'getProjects', etc.

	echo json_encode(['success' => false, 'error' => 'unknown_action']);
	exit;
}

/* ---------- NORMAL PAGE LOAD ---------- */
$loggedIn = isset($_SESSION['uid']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Punch List Software</title>
<meta charset="UTF-8">
<link rel="stylesheet" href="style.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
</head>

<body style="display: flex; justify-content: center; padding: 50px">

<!-- LOGIN SCREEN -->
<div class="loginScreen" id="loginScreen">
<h1>Login</h1>
<form id="loginForm">
<label for="email">Email:</label>
<input type="email" id="email" name="email" required><br><br>

<label for="pwd">Password:</label>
<input type="password" id="pwd" name="pwd" minlength="8" required><br><br>

<input type="submit" value="Login">
</form>
<p id="loginMsg" style="color: red;"></p>

<p>No account? <a href="#" id="toCreate">Create account here</a></p>
</div>

<!-- CREATE ACCOUNT SCREEN -->
<div class="createAccScreen" id="createAccScreen" style="display: none;">
<p class="introText">create account screen here!</p>
</div>

<!-- PROJECT DASHBOARD SCREEN -->
<div class="projDashboardScreen" id="projDashboardScreen" style="display: none;">
<p class="introText">project dashboard here!</p>
</div>

<!-- PROJECT DISPLAY SCREEN -->
<div class="projDisplayScreen" id="projDisplayScreen" style="display: none;">
<p class="introText">project display here!</p>
</div>

<script>
function showScreen(id) {
	$('#loginScreen, #createAccScreen, #projDashboardScreen, #projDisplayScreen').hide();
	$('#' + id).show();
}

// Helper: send JSON to this same file
function api(data) {
	return $.ajax({
		url: 'main.php',
		method: 'POST',
		contentType: 'application/json',
		data: JSON.stringify(data),
				  dataType: 'json'
	});
}

// If already logged in (session exists), skip the login screen
<?php if ($loggedIn): ?>
showScreen('projDashboardScreen');
<?php endif; ?>

$('#toCreate').on('click', function (e) {
	e.preventDefault();
	showScreen('createAccScreen');
});

$('#loginForm').on('submit', function (e) {
	e.preventDefault();
	$('#loginMsg').text('');

	api({
		action: 'login',
		email:  $('#email').val(),
		pwd:    $('#pwd').val()
	})
	.done(function (res) {
		if (res.success) {
			showScreen('projDashboardScreen');
		} else if (res.error === 'no_account') {
			$('#loginMsg').text('No account with this email.');
			setTimeout(function () { showScreen('createAccScreen'); }, 1500);
		} else if (res.error === 'wrong_password') {
			$('#loginMsg').text('Incorrect password.');
		} else {
			$('#loginMsg').text('Please fill in both fields.');
		}
	})
	.fail(function () {
		$('#loginMsg').text('Server error, try again.');
	});
});
</script>
</body>
</html>
