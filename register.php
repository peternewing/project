<?php
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (register_user($mysqli, $username, $email, $password)) {  // Pass $mysqli as the first argument
        header("Location: login.php");
        exit;
    } else {
        $error = "Registration failed. Try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <nav>
        <a href="index.php">Home</a>
    </nav>
    <form action="register.php" method="post">
        <h2>Register</h2>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        <label>Username:</label>
        <input type="text" name="username" required>
        <label>Email:</label>
        <input type="email" name="email" required>
        <label>Password:</label>
        <input type="password" name="password" required>
        <button type="submit">Register</button>
    </form>
</body>
</html>
