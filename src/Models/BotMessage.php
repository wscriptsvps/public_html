<?php
/**
 * Modelo BotMessage
 *
 * Responsável pelas operações na tabela de mensagens de bots.
 */
class BotMessage {

    /**
     * Obtém todas as mensagens de bot.
     * @return array
     */
    public static function getAll() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM bot_messages ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    /**
     * Obtém uma mensagem de bot aleatória.
     * @return mixed
     */
    public static function getRandom() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT message_text FROM bot_messages ORDER BY RAND() LIMIT 1");
        return $stmt->fetchColumn();
    }

    /**
     * Adiciona uma nova mensagem à lista.
     * @param string $message_text
     * @return bool
     */
    public static function create($message_text) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "INSERT INTO bot_messages (message_text) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$message_text]);
    }

    /**
     * Apaga uma mensagem da lista.
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("DELETE FROM bot_messages WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
