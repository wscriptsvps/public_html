<?php
/**
 * Endpoint para obter o estado completo da sala de uma só vez.
 * Retorna novas mensagens, a lista de utilizadores online e a votação ativa.
 */

require_once '../../src/config.php';
require_once '../../src/Core/Database.php';
require_once '../../src/Core/Auth.php';
require_once '../../src/Models/User.php';
require_once '../../src/Models/Message.php';
require_once '../../src/Models/VoteKick.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || !isset($_SESSION['current_room_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit();
}

$current_user_id = Auth::getUserId();
$room_id = $_SESSION['current_room_id'];
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

// Atualiza a atividade do utilizador
User::updateActivity($current_user_id);

// Busca todos os dados necessários
$messages = Message::getMessagesForUser($room_id, $current_user_id, $last_id);
$onlineUsers = User::getOnlineUsersInRoom($room_id);
$activeVote = VoteKick::getActiveVote($room_id);

// Adiciona o nome de exibição (display_name) a cada utilizador
foreach ($onlineUsers as &$user) {
    $user['display_name'] = $user['session_nickname'] ?? $user['nickname'] ?? $user['name'];
}

// Envia a resposta completa
echo json_encode([
    'status' => 'success',
    'messages' => $messages,
    'onlineUsers' => $onlineUsers,
    'activeVote' => $activeVote
]);
?>
