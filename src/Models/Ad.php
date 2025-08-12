<?php
/**
 * Modelo Ad
 *
 * Responsável por todas as operações do banco de dados relacionadas a anúncios.
 */
class Ad {

    /**
     * Obtém todos os anúncios.
     * @return array
     */
    public static function getAll() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM ads ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    /**
     * Encontra um anúncio pelo seu ID.
     * @param int $id
     * @return mixed
     */
    public static function findById($id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM ads WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Obtém um anúncio ativo aleatório para um local específico.
     * @param string $location ('homepage', 'chat_sidebar')
     * @return mixed
     */
    public static function getActiveAdForLocation($location) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT title, content_html FROM ads 
                WHERE status = 'active' AND display_location = ?
                ORDER BY RAND()
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$location]);
        return $stmt->fetch();
    }

    /**
     * Cria um novo anúncio.
     * @param string $title
     * @param string $content_html
     * @param string $display_location
     * @return bool
     */
    public static function create($title, $content_html, $display_location) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "INSERT INTO ads (title, content_html, display_location, status, created_at) 
                VALUES (?, ?, ?, 'active', NOW())";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$title, $content_html, $display_location]);
    }

    /**
     * Atualiza os dados de um anúncio.
     * @param int $id
     * @param string $title
     * @param string $content_html
     * @param string $display_location
     * @param string $status
     * @return bool
     */
    public static function update($id, $title, $content_html, $display_location, $status) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "UPDATE ads SET title = ?, content_html = ?, display_location = ?, status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$title, $content_html, $display_location, $status, $id]);
    }

    /**
     * Apaga um anúncio.
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("DELETE FROM ads WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
