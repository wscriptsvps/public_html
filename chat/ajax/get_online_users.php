<?php
require_once '../../src/config.php';
require_once '../../src/Core/Database.php';
require_once '../../src/Core/Auth.php';
require_once '../../src/Models/User.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || !isset($_SESSION['current_room_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit();
}

$room_id = $_SESSION['current_room_id'];
$onlineUsers = User::getOnlineUsersInRoom($room_id);

// Adiciona o nome de exibição (display_name) a cada utilizador
foreach ($onlineUsers as &$user) {
    // Prioridade: Apelido da sessão > Apelido VIP > Nome Real
    $user['display_name'] = $user['session_nickname'] ?? $user['nickname'] ?? $user['name'];
}

echo json_encode(['status' => 'success', 'users' => $onlineUsers]);
?>
