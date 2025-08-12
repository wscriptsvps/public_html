<?php
/**
 * Endpoint para registar a saída de um utilizador da sala.
 * Este script é chamado tanto pelo botão "Sair da Sala" como ao fechar a página.
 */

require_once '../../src/config.php';
require_once '../../src/Core/Database.php';
require_once '../../src/Core/Auth.php';
require_once '../../src/Models/Message.php';
require_once '../../src/Models/User.php';

// Verifica se o utilizador está logado
if (Auth::isLoggedIn()) {
    $user_id = Auth::getUserId();
    
    // Limpa a sala atual e o apelido da sessão do utilizador no banco de dados
    User::clearCurrentRoom($user_id);
    User::clearSessionNickname($user_id);

    // Se o utilizador estava numa sala, publica a mensagem de saída
    if (isset($_SESSION['current_room_id'])) {
        $room_id = $_SESSION['current_room_id'];
        if (isset($_SESSION['chat_ready'][$room_id])) {
            Message::create($user_id, $room_id, 'saiu da sala...', 'system');
            unset($_SESSION['chat_ready'][$room_id]);
        }
        // Limpa a sala atual da sessão
        unset($_SESSION['current_room_id']);
    }
}

// Responde com 204 No Content, que é apropriado para este tipo de pedido.
http_response_code(204);
