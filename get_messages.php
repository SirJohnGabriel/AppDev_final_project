<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

$chatWith = isset($_GET['chat_with']) ? intval($_GET['chat_with']) : 0;

if ($chatWith > 0) {
    $stmt = $pdo->prepare("SELECT messages.message, messages.sender_id, messages.timestamp, users.first_name as sender_first_name
        FROM messages
        JOIN users ON messages.sender_id = users.id
        WHERE (messages.sender_id = ? AND messages.receiver_id = ?)
           OR (messages.sender_id = ? AND messages.receiver_id = ?)
        ORDER BY messages.timestamp");
    $stmt->execute([$_SESSION['user_id'], $chatWith, $chatWith, $_SESSION['user_id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($messages);
}
?>
