<?php
/**
 * Modelo InfractionLog
 *
 * Responsável pelas operações na tabela de logs de infrações.
 */
class InfractionLog {

    /**
     * Cria um novo registo de infração.
     * @param int $user_id
     * @param string $ip_address
     * @param string $blocked_content
     * @param string $reason
     * @return bool
     */
    public static function create($user_id, $ip_address, $blocked_content, $reason) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "INSERT INTO infraction_logs (user_id, ip_address, blocked_content, reason, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$user_id, $ip_address, $blocked_content, $reason]);
    }

    /**
     * Obtém todos os logs de infrações, juntando o nome do utilizador.
     * @return array
     */
    public static function getAll() {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT i.*, u.name as user_name
                FROM infraction_logs i
                JOIN users u ON i.user_id = u.id
                ORDER BY i.created_at DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Apaga um log de infração.
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("DELETE FROM infraction_logs WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
