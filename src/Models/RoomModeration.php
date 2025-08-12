<?php
/**
 * Modelo RoomModeration
 *
 * Responsável pelas operações de moderação de salas (banimentos, etc.).
 */
class RoomModeration {

    /**
     * Bane um utilizador de uma sala por um determinado período.
     * @param int $room_id, $banned_user_id, $moderator_user_id
     * @param string $duration - Ex: '1 hour', '1 day', 'permanent'
     * @return bool
     */
    public static function banUser($room_id, $banned_user_id, $moderator_user_id, $duration) {
        $pdo = Database::getInstance()->getConnection();
        $expires_at = null;
        if ($duration !== 'permanent') {
            $expires_at = date('Y-m-d H:i:s', strtotime("+ " . $duration));
        }
        $sql = "INSERT INTO room_moderation (room_id, banned_user_id, moderator_user_id, ban_expires_at) 
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$room_id, $banned_user_id, $moderator_user_id, $expires_at]);
    }

    /**
     * Verifica se um utilizador está atualmente banido de uma sala.
     * @param int $user_id, $room_id
     * @return bool
     */
    public static function isUserBanned($user_id, $room_id) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT id FROM room_moderation 
                WHERE banned_user_id = ? AND room_id = ? AND (ban_expires_at IS NULL OR ban_expires_at > NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $room_id]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Obtém a lista de utilizadores banidos de uma sala.
     * @param int $room_id
     * @return array
     */
    public static function getBannedUsersForRoom($room_id) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT rm.id, rm.ban_expires_at, u.name as banned_user_name
                FROM room_moderation rm
                JOIN users u ON rm.banned_user_id = u.id
                WHERE rm.room_id = ? AND (rm.ban_expires_at IS NULL OR rm.ban_expires_at > NOW())
                ORDER BY rm.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$room_id]);
        return $stmt->fetchAll();
    }

    /**
     * Remove um banimento.
     * @param int $ban_id
     * @return bool
     */
    public static function unbanUser($ban_id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("DELETE FROM room_moderation WHERE id = ?");
        return $stmt->execute([$ban_id]);
    }
}
