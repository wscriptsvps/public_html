<?php
// Carregar a configuração e as classes necessárias
require_once '../../src/config.php';
require_once '../../src/Core/Database.php';
require_once '../../src/Core/Auth.php';
require_once '../../src/Models/User.php';
require_once '../../src/Models/Message.php';

header('Content-Type: application/json');

// Segurança: Verifica se o utilizador está logado e se entrou numa sala
if (!Auth::isLoggedIn() || !isset($_SESSION['current_room_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado ou sala não definida.']);
    exit();
}

$current_user_id = Auth::getUserId();
User::updateActivity($current_user_id);

// Obtém o ID da sala a partir da sessão para filtrar as mensagens
$room_id = $_SESSION['current_room_id'];
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

// LÓGICA ATUALIZADA: Busca apenas as mensagens da sala correta
$messages = Message::getMessagesForUser($room_id, $current_user_id, $last_id);

echo json_encode(['status' => 'success', 'messages' => $messages]);
?>
