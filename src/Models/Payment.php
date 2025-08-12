<?php
/**
 * Modelo Payment
 *
 * Responsável por todas as operações do banco de dados relacionadas a pagamentos.
 */
class Payment {

    /**
     * Cria um novo registo de pagamento no banco de dados.
     * @param int $user_id, $plan_id
     * @param float $amount
     * @param string $stripe_charge_id, $status
     * @return bool
     */
    public static function create($user_id, $plan_id, $amount, $stripe_charge_id, $status = 'succeeded') {
        $pdo = Database::getInstance()->getConnection();
        $sql = "INSERT INTO payments (user_id, plan_id, amount, stripe_charge_id, status, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$user_id, $plan_id, ($amount / 100), $stripe_charge_id, $status]);
    }

    /**
     * Obtém o histórico de pagamentos de um utilizador.
     * @param int $user_id
     * @return array
     */
    public static function getHistoryForUser($user_id) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT p.created_at, p.amount, p.status, p.stripe_charge_id, pl.name as plan_name
                FROM payments p
                JOIN plans pl ON p.plan_id = pl.id
                WHERE p.user_id = ?
                ORDER BY p.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Calcula a receita total do mês atual.
     * @return float
     */
    public static function getMonthlyRevenue() {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT SUM(amount) FROM payments 
                WHERE status = 'succeeded' 
                AND MONTH(created_at) = MONTH(CURRENT_DATE())
                AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        $stmt = $pdo->query($sql);
        return (float)$stmt->fetchColumn();
    }
}
