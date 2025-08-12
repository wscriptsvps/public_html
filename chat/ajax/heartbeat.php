<?php
/**
 * Endpoint de Heartbeat
 *
 * Recebe uma notificação periódica do cliente para manter
 * o status de 'última atividade' do utilizador atualizado.
 */

require_once '../../src/config.php';
require_once '../../src/Core/Database.php';
require_once '../../src/Core/Auth.php';
require_once '../../src/Models/User.php';

// Apenas executa se o utilizador estiver logado
if (Auth::isLoggedIn()) {
    $user_id = Auth::getUserId();
    User::updateActivity($user_id);
}

// Responde com 204 No Content, indicando sucesso sem necessidade de devolver conteúdo.
http_response_code(204);
