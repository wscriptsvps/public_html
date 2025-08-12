<?php
/**
 * Endpoint para receber e guardar uma denúncia de utilizador.
 */

require_once '../../src/config.php';
require_once '../../src/Core/Database.php';
require_once '../../src/Core/Auth.php';
require_once '../../src/Models/Report.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['current_room_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado.']);
    exit();
}

$reporter_user_id = Auth::getUserId();
$room_id = $_SESSION['current_room_id'];

$reported_user_id = $_POST['reported_user_id'] ?? null;
$reason = $_POST['reason'] ?? 'Não especificado';
$description = $_POST['description'] ?? '';

if (empty($reported_user_id) || empty($reason)) {
    echo json_encode(['status' => 'error', 'message' => 'Dados da denúncia incompletos.']);
    exit();
}

if (Report::create($reported_user_id, $reporter_user_id, $room_id, $reason, $description)) {
    echo json_encode(['status' => 'success', 'message' => 'Denúncia enviada com sucesso. A nossa equipa de moderação irá analisar.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Ocorreu um erro ao enviar a sua denúncia.']);
}
?>
