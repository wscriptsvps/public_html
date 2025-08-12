<?php
/**
 * Script CRON para Manter os Bots Ativos e em Salas
 *
 * Este script deve ser executado a cada minuto para:
 * 1. Atualizar a 'last_activity' de todos os bots para mantê-los online.
 * 2. Atribuir bots que não estão em nenhuma sala a uma sala ativa aleatoriamente.
 */

// Carrega os ficheiros essenciais do projeto
require_once dirname(__DIR__) . '/src/config.php';
require_once dirname(__DIR__) . '/src/Core/Database.php';
require_once dirname(__DIR__) . '/src/Models/User.php'; // Adicionado para usar os métodos do User

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Etapa 1: Atualiza a última atividade de todos os utilizadores que são bots
    $stmt_update = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE account_type = 'bot'");
    $stmt_update->execute();
    $updated_count = $stmt_update->rowCount();

    echo "{$updated_count} bots tiveram a sua atividade atualizada.\n";

    // Etapa 2: Encontra bots que não estão em nenhuma sala
    $stmt_homeless_bots = $pdo->query("SELECT id FROM users WHERE account_type = 'bot' AND current_room_id IS NULL");
    $homeless_bots = $stmt_homeless_bots->fetchAll(PDO::FETCH_COLUMN);

    if (empty($homeless_bots)) {
        echo "Nenhum bot fora de uma sala. Processo concluído.\n";
        exit;
    }

    // Etapa 3: Encontra todas as salas ativas
    $stmt_active_rooms = $pdo->query("SELECT id FROM rooms WHERE status = 'active'");
    $active_rooms = $stmt_active_rooms->fetchAll(PDO::FETCH_COLUMN);

    if (empty($active_rooms)) {
        echo "Nenhuma sala ativa encontrada para alocar os bots.\n";
        exit;
    }

    // Etapa 4: Atribui cada bot "sem-teto" a uma sala ativa aleatoriamente
    $assigned_count = 0;
    foreach ($homeless_bots as $bot_id) {
        $random_room_id = $active_rooms[array_rand($active_rooms)];
        if (User::setCurrentRoom($bot_id, $random_room_id)) {
            $assigned_count++;
        }
    }

    echo "{$assigned_count} bots foram atribuídos a salas ativas.\n";
    echo "Processo concluído.";

} catch (Exception $e) {
    error_log("Erro no CRON de bots: " . $e->getMessage());
    echo "Erro ao atualizar os bots.";
}
