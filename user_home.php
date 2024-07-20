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

$stmt = $pdo->prepare("SELECT profile_picture, first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$profilePicture = !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'assets/profile-pictures/def_profile.jpg';
$defaultName = 'Default Name';
$name = !empty($user['first_name']) && !empty($user['last_name']) ? htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) : $defaultName;

$stmt = $pdo->prepare("SELECT id, first_name, last_name, profile_picture FROM users WHERE id != ?");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll();

$chatWith = isset($_GET['chat_with']) ? intval($_GET['chat_with']) : 0;
$chatPartnerName = '';

if ($chatWith > 0) {
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$chatWith]);
    $chatPartner = $stmt->fetch();
    $chatPartnerName = !empty($chatPartner['first_name']) && !empty($chatPartner['last_name']) ? htmlspecialchars($chatPartner['first_name'] . ' ' . $chatPartner['last_name']) : 'Unknown User';

    $stmt = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY timestamp");
    $stmt->execute([$_SESSION['user_id'], $chatWith, $chatWith, $_SESSION['user_id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $messages = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Home</title>
    <link rel="stylesheet" href="css/user_home_style.css">
    <link rel="stylesheet" href="css/fonts.css">
</head>
<body>
    <div class="main-container montserrat-regular">
        <div class="left-container">
            <div class="top-left-container montserrat-regular">
                <div class="profile-picture">
                    <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture">
                </div>
                <p><?php echo htmlspecialchars($name); ?></p>
                <div class="button-container">
                    <button class="settings-btn" onclick="location.href='settings.php'">Settings</button>
                    <form action="logout.php" method="POST">
                        <button type="submit" name="logout" class="logout-btn">Logout</button>
                    </form>
                </div>
            </div>
            <div class="bottom-left-container">
                <h3>Chat with other users.</h3>
                <ul class="recent-chats roboto-bold">
                    <?php foreach ($users as $userItem): ?>
                        <li>
                            <img src="<?php echo htmlspecialchars(!empty($userItem['profile_picture']) ? $userItem['profile_picture'] : 'assets/profile-pictures/def_profile.jpg'); ?>" alt="Profile Picture" class="recent-chat-profile-pic">
                            <a href="?chat_with=<?php echo htmlspecialchars($userItem['id']); ?>">
                                <?php echo htmlspecialchars($userItem['first_name'] . ' ' . $userItem['last_name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="right-container">
            <div class="chat-header">
                <h2>Chat with <?php echo htmlspecialchars($chatPartnerName); ?></h2>
            </div>
            <div class="chat-container">
                <div id="messages">
                    <?php foreach ($messages as $msg): ?>
                        <div id="message-<?php echo htmlspecialchars($msg['id']); ?>" class="message <?php echo ($msg['sender_id'] == $_SESSION['user_id']) ? 'sent-message' : 'received-message'; ?>" data-message-id="<?php echo htmlspecialchars($msg['id']); ?>">
                            <?php echo htmlspecialchars($msg['message']);?><br>
                            <small><?php echo (new DateTime($msg['timestamp']))->format('Y-m-d H:i:s'); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form id="message-form">
                    <input type="text" id="message-input" placeholder="Type a message">
                    <button type="submit">Send</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        const chatWith = <?php echo json_encode($chatWith); ?>;
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            function loadMessages() {
                fetch(`get_messages.php?chat_with=${chatWith}`)
                    .then(response => response.json())
                    .then(messages => {
                        const messagesDiv = document.getElementById('messages');
                        messagesDiv.innerHTML = '';
                        messages.forEach(msg => {
                            const messageElement = document.createElement('div');
                            messageElement.classList.add('message');
                            messageElement.classList.add(msg.sender_id === currentUserId ? 'sent-message' : 'received-message');
                            const timestamp = new Date(msg.timestamp).toLocaleString();

                            messageElement.innerHTML = `
                                ${msg.message} <br><small>${timestamp}</small>
                            `;
                            
                            messagesDiv.appendChild(messageElement);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading messages:', error);
                    });
            }

            if (chatWith > 0) {
                loadMessages();
            }

            document.getElementById('message-form').addEventListener('submit', function(event) {
                event.preventDefault();
                const messageInput = document.getElementById('message-input');
                const message = messageInput.value;
                if (message.trim()) {
                    fetch('send_message.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `receiver_id=${chatWith}&message=${encodeURIComponent(message)}`
                    }).then(response => response.text())
                    .then(() => {
                        messageInput.value = '';
                        loadMessages();
                    });
                }
            });
        });
    </script>
</body>
</html>
