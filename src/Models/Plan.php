<?php
/**
 * Modelo Plan
 *
 * Responsável por todas as operações do banco de dados relacionadas a planos de assinatura.
 */
class Plan {

    /**
     * Obtém todos os planos de assinatura.
     * @return array
     */
    public static function getAll() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM plans ORDER BY price ASC");
        return $stmt->fetchAll();
    }

    /**
     * Encontra um plano pelo seu ID.
     * @param int $id
     * @return mixed
     */
    public static function findById($id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Cria um novo plano de assinatura.
     * @param string $name
     * @param float $price
     * @param int $interval_months
     * @param int $room_creation_limit
     * @return bool
     */
    public static function create($name, $price, $interval_months, $room_creation_limit) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "INSERT INTO plans (name, price, interval_months, room_creation_limit, status) 
                VALUES (?, ?, ?, ?, 'active')";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$name, $price, $interval_months, $room_creation_limit]);
    }

    /**
     * Atualiza os dados de um plano.
     * @param int $id
     * @param string $name
     * @param float $price
     * @param int $interval_months
     * @param int $room_creation_limit
     * @param string $status
     * @return bool
     */
    public static function update($id, $name, $price, $interval_months, $room_creation_limit, $status) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "UPDATE plans SET name = ?, price = ?, interval_months = ?, room_creation_limit = ?, status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$name, $price, $interval_months, $room_creation_limit, $status, $id]);
    }

    /**
     * Apaga um plano.
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("DELETE FROM plans WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
