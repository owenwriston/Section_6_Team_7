<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Punch List Software</title>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="style.css">
		<script src=" https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    </head>

    <body style="justify-content: display: flex; padding: 50px">
		<!-- LOGIN SCREEN -->
		<div class="loginScreen" id="loginScreen">
			<h1>Login</h1>
			<form action="login_transition.php" method="POST">
				<label for="email">Email:</label>
				<input type="email" id="email" name="email" required><br><br>

				<label for="pwd">Password:</label>
				<input type="password" id="pwd" name="pwd" minlength="8" required><br><br>

				<input type="submit" value="Login">
			</form>

			<p>
				No account?
				<a href="main.php?screen=createUser">Create account here</a>
			</p>

			<!--
				GOAL HERE:
				(1) save login data to login_data.txt
				(2) check if $data is in user_data.txt
				(2a) if so, go to project dashboard screen
				(2b) if not, say "No account with this email" and redirect user to create-account screen
			-->
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
    </body>
