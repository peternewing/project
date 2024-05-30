<?php
include __DIR__ . '/includes/config.php';
include __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (login_user($mysqli, $username, $password)) {  // Pass $mysqli as the first argument
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid login credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <nav>
        <a href="index.php">Home</a>
    </nav>
    <form action="login.php" method="post">
        <h2>Login</h2>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        <label>Username:</label>
        <input type="text" name="username" required>
        <label>Password:</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
</body>
</html>
