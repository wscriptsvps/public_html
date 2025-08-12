<?php
/**
 * Endpoint para processar ações de administração dentro da sala de chat.
 */

require_once '../../src/config.php';
require_once '../../src/Core/Database.php';
require_once '../../src/Core/Auth.php';
require_once '../../src/Models/User.php';
require_once '../../src/Models/Message.php';

header('Content-Type: application/json');

// Segurança: Apenas administradores podem executar estas ações
if (!Auth::isLoggedIn() || $_SESSION['user_account_type'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit();
}

$action = $_POST['action'] ?? null;
$user_id = Auth::getUserId();

switch ($action) {
    case 'ban_user':
        $user_to_ban_id = $_POST['user_id'] ?? null;
        if ($user_to_ban_id) {
            if (User::updateStatus($user_to_ban_id, 'banned')) {
                echo json_encode(['status' => 'success', 'message' => 'Utilizador banido com sucesso.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Erro ao banir o utilizador.']);
            }
        }
        break;

    case 'clear_room':
        $room_id = $_SESSION['current_room_id'] ?? null;
        if ($room_id) {
            if (Message::clearRoom($room_id)) {
                // Adiciona uma mensagem de sistema informando da limpeza
                Message::create($user_id, $room_id, 'limpou a sala.', 'system');
                echo json_encode(['status' => 'success', 'message' => 'Sala limpa com sucesso.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Erro ao limpar a sala.']);
            }
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Ação inválida.']);
        break;
}
?>
