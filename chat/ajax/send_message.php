<?php
// Carregar a configuração e as classes necessárias
require_once '../../src/config.php';
require_once '../../src/Core/Database.php';
require_once '../../src/Core/Auth.php';
require_once '../../src/Models/User.php';
require_once '../../src/Models/Message.php';
require_once '../../src/Core/Filter.php';
require_once '../../src/Models/BlockedWord.php';
require_once '../../src/Models/InfractionLog.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['current_room_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado ou sala não definida.']);
    exit();
}

$content = trim($_POST['content'] ?? '');
$user_id = Auth::getUserId();
$room_id = $_SESSION['current_room_id'];

$validation_result = Filter::validate($content, $user_id);
if ($validation_result !== true) {
    echo json_encode(['status' => 'error', 'message' => $validation_result]);
    exit();
}

// LÓGICA ATUALIZADA
$recipient_id = isset($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : null;
$is_private = isset($_POST['is_private']) && $_POST['is_private'] === 'true';

$type = 'public';
if ($is_private && $recipient_id) {
    $type = 'private';
}

if (!empty($content) && $user_id) {
    // O recipient_id é guardado tanto para mensagens públicas direcionadas como para privadas
    if (Message::create($user_id, $room_id, $content, $type, $recipient_id)) {
        User::updateActivity($user_id);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Falha ao enviar a mensagem.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'A mensagem não pode estar vazia.']);
}
?>
