<?php
session_start();
include 'db.php'; 

if (isset($_SESSION['user_id'])) {
    header('Location: user_home.php');
    exit();
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = $user['id'];

        if ($remember) {
            setcookie('username', $username, time() + (86400 * 30), "/");
            setcookie('password', $password, time() + (86400 * 30), "/");
        } else {
            setcookie('username', '', time() - 3600, "/");
            setcookie('password', '', time() - 3600, "/");
        }

        header("Location: user_home.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

$savedUsername = isset($_COOKIE['username']) ? htmlspecialchars($_COOKIE['username']) : '';
$savedPassword = isset($_COOKIE['password']) ? htmlspecialchars($_COOKIE['password']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/index_style.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
</head>
<body>
    <div class="title-container montserrat-light">
        <h2>MESSAGE.io</h2>
    </div>
    <div class="form-container">
        <h2 class="montserrat-bold">Welcome.</h2>
        <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
        <form method="POST" action="">
            <label for="username" class="roboto-light">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo $savedUsername; ?>" required>
            <label for="password" class="roboto-light">Password:</label>
            <input type="password" id="password" name="password" value="<?php echo $savedPassword; ?>" required>
            <div class="remember">
                <input type="checkbox" id="remember" name="remember" <?php if (isset($_COOKIE['username'])) echo 'checked'; ?>>
                <label for="remember" class="roboto-light">Remember Me</label>
            </div>
            <button class="submit-btn montserrat-bold" type="submit" name="login">NEXT</button>
        </form>
        <p class="register-label montserrat-regular">Don't have an account?<a href="register.php"> Sign Up</a></p>
    </div>
</body>
</html>
