<?php
/**
 * Modelo Report
 *
 * Responsável por todas as operações do banco de dados relacionadas a denúncias.
 */
class Report {

    /**
     * Cria uma nova denúncia.
     * @param int $reported_user_id
     * @param int $reporter_user_id
     * @param int $room_id
     * @param string $reason
     * @param string $description
     * @return bool
     */
    public static function create($reported_user_id, $reporter_user_id, $room_id, $reason, $description) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "INSERT INTO reports (reported_user_id, reporter_user_id, room_id, reason, description, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$reported_user_id, $reporter_user_id, $room_id, $reason, $description]);
    }

    /**
     * Obtém todas as denúncias, juntando os nomes dos utilizadores envolvidos.
     * @return array
     */
    public static function getAll() {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT 
                    r.id, r.reason, r.status, r.created_at,
                    reported.name as reported_user_name,
                    reporter.name as reporter_user_name
                FROM reports r
                JOIN users AS reported ON r.reported_user_id = reported.id
                JOIN users AS reporter ON r.reporter_user_id = reporter.id
                ORDER BY r.created_at DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Encontra uma denúncia pelo seu ID, com todos os detalhes.
     * @param int $id
     * @return mixed
     */
    public static function findById($id) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT 
                    r.*,
                    reported.name as reported_user_name,
                    reporter.name as reporter_user_name,
                    rm.name as room_name
                FROM reports r
                JOIN users AS reported ON r.reported_user_id = reported.id
                JOIN users AS reporter ON r.reporter_user_id = reporter.id
                LEFT JOIN rooms rm ON r.room_id = rm.id
                WHERE r.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Atualiza o status de uma denúncia.
     * @param int $id
     * @param string $status ('new', 'reviewed', 'closed')
     * @return bool
     */
    public static function updateStatus($id, $status) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
}
