<?php
/**
 * Modelo VoteKick
 *
 * Responsável pelas operações do sistema de votação para expulsão.
 */
class VoteKick {

    /**
     * Inicia uma nova votação para expulsar um utilizador.
     * @param int $room_id, $target_user_id, $initiator_user_id
     * @return int|false - O ID da nova votação ou false em caso de falha.
     */
    public static function startVote($room_id, $target_user_id, $initiator_user_id) {
        $pdo = Database::getInstance()->getConnection();
        // Votação expira em 2 minutos
        $expires_at = date('Y-m-d H:i:s', strtotime("+2 minutes"));
        $sql = "INSERT INTO vote_kicks (room_id, target_user_id, initiator_user_id, expires_at) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$room_id, $target_user_id, $initiator_user_id, $expires_at])) {
            return $pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Regista um voto de um utilizador numa votação ativa.
     * @param int $vote_kick_id, $voter_user_id
     * @return bool
     */
    public static function castVote($vote_kick_id, $voter_user_id) {
        $pdo = Database::getInstance()->getConnection();
        try {
            $sql = "INSERT INTO vote_kick_casts (vote_kick_id, voter_user_id) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$vote_kick_id, $voter_user_id]);
        } catch (PDOException $e) {
            // Ignora o erro se o utilizador já votou (devido à constraint UNIQUE)
            return false;
        }
    }

    /**
     * Obtém a votação ativa numa sala, se existir.
     * @param int $room_id
     * @return mixed
     */
    public static function getActiveVote($room_id) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT vk.*, u.name as target_user_name, i.name as initiator_user_name,
                       (SELECT COUNT(id) FROM vote_kick_casts WHERE vote_kick_id = vk.id) as vote_count
                FROM vote_kicks vk
                JOIN users u ON vk.target_user_id = u.id
                JOIN users i ON vk.initiator_user_id = i.id
                WHERE vk.room_id = ? AND vk.status = 'active' AND vk.expires_at > NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$room_id]);
        return $stmt->fetch();
    }

    /**
     * Finaliza uma votação.
     * @param int $vote_kick_id
     * @return bool
     */
    public static function finishVote($vote_kick_id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE vote_kicks SET status = 'finished' WHERE id = ?");
        return $stmt->execute([$vote_kick_id]);
    }
}
