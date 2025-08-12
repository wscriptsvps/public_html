<?php
/**
 * Endpoint para processar ações de votação para expulsão.
 */

require_once '../../src/config.php';
require_once '../../src/Core/Database.php';
require_once '../../src/Core/Auth.php';
require_once '../../src/Models/User.php';
require_once '../../src/Models/Room.php';
require_once '../../src/Models/Message.php';
require_once '../../src/Models/RoomModeration.php';
require_once '../../src/Models/VoteKick.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['current_room_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit();
}

$action = $_POST['action'] ?? null;
$room_id = $_SESSION['current_room_id'];
$user_id = Auth::getUserId();

switch ($action) {
    case 'start_vote':
        $target_user_id = (int)($_POST['target_user_id'] ?? 0);

        if (VoteKick::getActiveVote($room_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Já existe uma votação em andamento.']);
            exit;
        }

        $online_users_count = count(User::getOnlineUsersInRoom($room_id));
        if ($online_users_count < 3) { // Mínimo de 3 utilizadores
            echo json_encode(['status' => 'error', 'message' => 'São necessários pelo menos 3 utilizadores na sala para iniciar uma votação.']);
            exit;
        }
        
        $vote_id = VoteKick::startVote($room_id, $target_user_id, $user_id);
        if ($vote_id) {
            VoteKick::castVote($vote_id, $user_id); // O iniciador vota automaticamente
            $target_user = User::findById($target_user_id);
            Message::create($user_id, $room_id, "iniciou uma votação para expulsar {$target_user['name']}.", 'system');
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao iniciar a votação.']);
        }
        break;

    case 'cast_vote':
        $vote_id = (int)($_POST['vote_id'] ?? 0);
        
        if (VoteKick::castVote($vote_id, $user_id)) {
            $vote = VoteKick::getActiveVote($room_id);
            if ($vote && $vote['vote_count'] >= 10) {
                $target_user = User::findById($vote['target_user_id']);
                RoomModeration::banUser($room_id, $vote['target_user_id'], $vote['initiator_user_id'], '24 hours', $target_user['last_ip']);
                User::clearCurrentRoom($vote['target_user_id']);
                User::clearSessionNickname($vote['target_user_id']);
                VoteKick::finishVote($vote_id);
                Message::create(Auth::getUserId(), $room_id, "{$target_user['name']} foi expulso da sala por votação.", 'system');
            }
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Você já votou ou a votação expirou.']);
        }
        break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Ação inválida.']);
        break;
}
