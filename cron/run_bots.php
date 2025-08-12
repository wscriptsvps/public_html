<?php
/**
 * Script CRON para Fazer os Bots Interagirem
 *
 * Este script deve ser executado periodicamente (ex: a cada 5 minutos) para
 * que os bots enviem mensagens nas salas ativas.
 */

// Carrega os ficheiros essenciais do projeto
require_once dirname(__DIR__) . '/src/config.php';
require_once dirname(__DIR__) . '/src/Core/Database.php';
require_once dirname(__DIR__) . '/src/Models/Room.php';
require_once dirname(__DIR__) . '/src/Models/User.php';
require_once dirname(__DIR__) . '/src/Models/Message.php';
require_once dirname(__DIR__) . '/src/Models/BotMessage.php';

try {
    $pdo = Database::getInstance()->getConnection();

    // 1. Obtém todas as salas ativas
    $active_rooms = $pdo->query("SELECT id FROM rooms WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($active_rooms)) {
        echo "Nenhuma sala ativa encontrada.";
        exit;
    }

    // 2. Itera sobre cada sala
    foreach ($active_rooms as $room_id) {
        // 3. Verifica se há utilizadores reais na sala
        $stmt_real_users = $pdo->prepare("SELECT COUNT(id) FROM users WHERE current_room_id = ? AND account_type != 'bot'");
        $stmt_real_users->execute([$room_id]);
        $real_users_count = $stmt_real_users->fetchColumn();

        // Só envia mensagem se houver pelo menos um utilizador real
        if ($real_users_count > 0) {
            // 4. Obtém um bot aleatório que está nesta sala
            $stmt_bot = $pdo->prepare("SELECT id FROM users WHERE current_room_id = ? AND account_type = 'bot' ORDER BY RAND() LIMIT 1");
            $stmt_bot->execute([$room_id]);
            $bot_id = $stmt_bot->fetchColumn();

            if ($bot_id) {
                // 5. Obtém uma mensagem aleatória para o bot dizer
                $bot_message = BotMessage::getRandom();

                if ($bot_message) {
                    // 6. Publica a mensagem na sala em nome do bot
                    Message::create($bot_id, $room_id, $bot_message, 'public');
                    echo "Bot #{$bot_id} enviou a mensagem '{$bot_message}' na sala #{$room_id}.\n";
                }
            }
        }
    }
    echo "Processo de bots concluído.";

} catch (Exception $e) {
    error_log("Erro no CRON de interação de bots: " . $e->getMessage());
    echo "Erro ao executar o script de bots.";
}
