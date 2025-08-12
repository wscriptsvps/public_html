<?php
/**
 * Endpoint para processar ações de moderação de sala por VIPs.
 */

require_once '../../src/config.php';
require_once '../../src/Core/Database.php';
require_once '../../src/Core/Auth.php';
require_once '../../src/Models/User.php';
require_once '../../src/Models/Room.php';
require_once '../../src/Models/Message.php';
require_once '../../src/Models/RoomModeration.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['current_room_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit();
}

$moderator_id = Auth::getUserId();
$room_id = $_SESSION['current_room_id'];
$room = Room::findById($room_id);

// Segurança: Apenas o criador da sala ou um admin pode moderar
if ($room['created_by_user_id'] != $moderator_id && $_SESSION['user_account_type'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Você não tem permissão para moderar esta sala.']);
    exit();
}

$action = $_POST['action'] ?? null;
$target_user_id = $_POST['target_user_id'] ?? null;

if (!$target_user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Utilizador alvo não especificado.']);
    exit();
}

$target_user = User::findById($target_user_id);
if (!$target_user) {
    echo json_encode(['status' => 'error', 'message' => 'Utilizador alvo não encontrado.']);
    exit();
}


switch ($action) {
    case 'kick':
        User::clearCurrentRoom($target_user_id);
        User::clearSessionNickname($target_user_id);
        Message::create($moderator_id, $room_id, "expulsou {$target_user['name']} da sala.", 'system');
        echo json_encode(['status' => 'success', 'message' => "{$target_user['name']} foi expulso."]);
        break;

    case 'ban':
        $duration = $_POST['duration'] ?? '1 hour';
        RoomModeration::banUser($room_id, $target_user_id, $moderator_id, $duration);
        User::clearCurrentRoom($target_user_id);
        User::clearSessionNickname($target_user_id);
        Message::create($moderator_id, $room_id, "baniu {$target_user['name']} da sala.", 'system');
        echo json_encode(['status' => 'success', 'message' => "{$target_user['name']} foi banido."]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Ação inválida.']);
        break;
}
