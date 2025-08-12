<?php
/**
 * Modelo Contact
 *
 * Responsável por todas as operações do banco de dados relacionadas a mensagens de contato.
 */
class Contact {

    /**
     * Cria uma nova mensagem de contato.
     * @param string $name
     * @param string $email
     * @param string $subject
     * @param string $message
     * @return bool
     */
    public static function create($name, $email, $subject, $message) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "INSERT INTO contacts (name, email, subject, message, status, created_at) 
                VALUES (?, ?, ?, ?, 'new', NOW())";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$name, $email, $subject, $message]);
    }

    /**
     * Obtém todas as mensagens de contato.
     * @return array
     */
    public static function getAll() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT id, name, email, subject, status, created_at FROM contacts ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    /**
     * Encontra uma mensagem de contato pelo seu ID.
     * @param int $id
     * @return mixed
     */
    public static function findById($id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Atualiza o status de uma mensagem.
     * @param int $id
     * @param string $status ('new', 'read', 'archived')
     * @return bool
     */
    public static function updateStatus($id, $status) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE contacts SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Apaga uma mensagem de contato.
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
