<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if ($newPassword === $confirmPassword) {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (password_verify($currentPassword, $user['password'])) {
                $hashedNewPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedNewPassword, $_SESSION['user_id']]);
                $message = 'Password updated successfully.';
            } else {
                $error = 'Current password is incorrect.';
            }
        } else {
            $error = 'New passwords do not match.';
        }
    } elseif (isset($_POST['update_picture'])) {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
            $fileName = $_FILES['profile_picture']['name'];
            $fileSize = $_FILES['profile_picture']['size'];
            $fileType = $_FILES['profile_picture']['type'];
            $fileNameCmps = explode('.', $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($fileExtension, $allowedExts)) {
                $uploadFileDir = './assets/profile-pictures/';
                $dest_path = $uploadFileDir . $_SESSION['user_id'] . '.' . $fileExtension;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    $stmt->execute([$dest_path, $_SESSION['user_id']]);
                    $message = 'Profile picture updated successfully.';
                } else {
                    $error = 'Error uploading file.';
                }
            } else {
                $error = 'Invalid file type.';
            }
        } else {
            $error = 'No file uploaded or upload error.';
        }
    }
}

$stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$currentProfilePicture = $user['profile_picture'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="css/settings_style.css">
    <link rel="stylesheet" href="css/fonts.css">
</head>
<body>
    <div class="main-container">
        <div class="container-header">
            <h1>Settings</h1>
            <a href="user_home.php" class="back-btn">Back</a>
        </div>
        <div class="sub-container montserrat-regular">
            <form action="settings.php" method="POST">
                <h2>Change Password</h2>
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" required><br>
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required><br>
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required><br>
                <button class="smbt-btn" type="submit" name="change_password">Change Password</button>
            </form>
    
            <form action="settings.php" method="POST" enctype="multipart/form-data">
                <h2>Change Profile Picture</h2>
                <?php if ($currentProfilePicture): ?>
                    <img src="<?php echo htmlspecialchars($currentProfilePicture); ?>" alt="Current Profile Picture" class="profile-pic-preview">
                <?php endif; ?>
                <label for="profile_picture">Upload New Profile Picture:</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*"><br>
                <button type="submit" name="update_picture">Update Picture</button>
            </form>
            
            <?php if (!empty($message)): ?>
                <p class="success-message"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
