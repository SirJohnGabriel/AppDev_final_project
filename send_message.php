<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

if (isset($_POST['receiver_id']) && isset($_POST['message'])) {
    $receiverId = intval($_POST['receiver_id']);
    $message = trim($_POST['message']);
    $senderId = $_SESSION['user_id'];

    if ($receiverId > 0 && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$senderId, $receiverId, $message]);
        echo 'Message sent!';
    } else {
        echo 'Invalid input!';
    }
} else {
    echo 'No data received!';
}
?>
