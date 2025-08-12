<?php
/**
 * Endpoint para obter o ID da última mensagem de uma sala.
 */

require_once '../../src/config.php';
require_once '../../src/Core/Database.php';
require_once '../../src/Core/Auth.php';
require_once '../../src/Models/Message.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || !isset($_SESSION['current_room_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit();
}

$room_id = $_SESSION['current_room_id'];
$last_id = Message::getLastMessageId($room_id);

echo json_encode(['status' => 'success', 'last_id' => $last_id]);
?>
