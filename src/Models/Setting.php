<?php
/**
 * Modelo Setting
 *
 * Responsável por obter e definir configurações guardadas no banco de dados.
 */
class Setting {

    /**
     * Obtém o valor de uma configuração específica.
     * @param string $key - A chave da configuração (ex: 'stripe_mode').
     * @return string|null - O valor da configuração ou null se não for encontrada.
     */
    public static function get($key) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn();
    }

    /**
     * Define ou atualiza o valor de uma configuração.
     * @param string $key - A chave da configuração.
     * @param string $value - O novo valor.
     * @return bool - True em caso de sucesso, false em caso de falha.
     */
    public static function set($key, $value) {
        $pdo = Database::getInstance()->getConnection();
        // Usa ON DUPLICATE KEY UPDATE para inserir se não existir, ou atualizar se já existir.
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$key, $value, $value]);
    }
}
