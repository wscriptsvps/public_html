<?php
/**
 * Modelo Message
 *
 * Responsável por todas as operações do banco de dados relacionadas a mensagens.
 */
class Message {

    /**
     * Cria uma nova mensagem no banco de dados.
     * @param int $user_id, $room_id
     * @param string $content, $type
     * @param int|null $recipient_id
     * @return bool
     */
    public static function create($user_id, $room_id, $content, $type = 'public', $recipient_id = null) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "INSERT INTO messages (user_id, room_id, content, type, recipient_id, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$user_id, $room_id, $content, $type, $recipient_id]);
    }

    /**
     * Obtém as mensagens de uma sala visíveis para um utilizador específico.
     * @param int $room_id, $current_user_id, $last_id
     * @param int $limit
     * @return array
     */
    public static function getMessagesForUser($room_id, $current_user_id, $last_id = 0, $limit = 50) {
        $pdo = Database::getInstance()->getConnection();
        
        // A consulta foi atualizada para buscar o nome de exibição correto (apelido ou nome)
        $sql = "SELECT m.id, m.content, m.created_at, m.type, m.recipient_id,
                       u.id as user_id, 
                       COALESCE(u.session_nickname, u.nickname, u.name) as display_name,
                       u.avatar as user_avatar, u.color as user_color,
                       COALESCE(r.session_nickname, r.nickname, r.name) as recipient_display_name
                FROM messages m
                JOIN users u ON m.user_id = u.id
                LEFT JOIN users r ON m.recipient_id = r.id
                WHERE m.room_id = ? AND m.id > ? AND
                      (m.type = 'public' OR m.type = 'system' OR
                      (m.type = 'private' AND (m.user_id = ? OR m.recipient_id = ?)))
                ORDER BY m.id ASC
                LIMIT ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$room_id, $last_id, $current_user_id, $current_user_id, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Apaga TODAS as mensagens de uma sala específica.
     * @param int $room_id
     * @return bool
     */
    public static function clearRoom($room_id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("DELETE FROM messages WHERE room_id = ?");
        return $stmt->execute([$room_id]);
    }

    /**
     * Obtém o ID da última mensagem de uma sala.
     * @param int $room_id
     * @return int
     */
    public static function getLastMessageId($room_id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT MAX(id) FROM messages WHERE room_id = ?");
        $stmt->execute([$room_id]);
        return (int)$stmt->fetchColumn();
    }
}
