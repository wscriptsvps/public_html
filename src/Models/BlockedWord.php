<?php
/**
 * Modelo BlockedWord
 *
 * Responsável pelas operações na tabela de palavras bloqueadas.
 */
class BlockedWord {

    /**
     * Obtém todas as palavras bloqueadas.
     * @return array
     */
    public static function getAll() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM blocked_words ORDER BY word ASC");
        return $stmt->fetchAll();
    }

    /**
     * Adiciona uma nova palavra à lista.
     * @param string $word
     * @return bool
     */
    public static function create($word) {
        $pdo = Database::getInstance()->getConnection();
        try {
            $sql = "INSERT INTO blocked_words (word) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([strtolower($word)]);
        } catch (PDOException $e) {
            // Ignora o erro se a palavra já existir (devido à constraint UNIQUE)
            return false;
        }
    }

    /**
     * Apaga uma palavra da lista.
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("DELETE FROM blocked_words WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
