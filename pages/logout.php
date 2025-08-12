<?php
// As classes Auth e Message já foram carregadas pelo index.php

if (Auth::isLoggedIn()) {
    // Se o utilizador estava na sessão de chat, cria a mensagem de saída
    if (isset($_SESSION['chat_ready']) && $_SESSION['chat_ready'] === true) {
        $user_id = Auth::getUserId();
        $room_id = 1; // Sala padrão
        Message::create($user_id, $room_id, 'saiu da sala...', 'system');
    }
}

// Efetua o logout do utilizador
Auth::logout();

// Redireciona o utilizador para a página inicial
header('Location: /');
exit();
?>
