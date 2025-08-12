<?php
/**
 * Modelo Avatar
 *
 * Responsável por todas as operações do banco de dados relacionadas a avatares padrão.
 */
class Avatar {

    /**
     * Obtém todos os avatares padrão.
     * @return array
     */
    public static function getAll() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM default_avatars ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    /**
     * Obtém o caminho de um avatar padrão aleatório.
     * @return string|false
     */
    public static function getRandom() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT path FROM default_avatars ORDER BY RAND() LIMIT 1");
        $result = $stmt->fetchColumn();
        // Se não houver avatares padrão, retorna o default.png
        return $result ?: 'default.png';
    }

    /**
     * Adiciona um novo avatar padrão ao banco de dados.
     * @param string $path
     * @param string $category
     * @return bool
     */
    public static function create($path, $category = 'general') {
        $pdo = Database::getInstance()->getConnection();
        $sql = "INSERT INTO default_avatars (path, category) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$path, $category]);
    }

    /**
     * Apaga um avatar padrão.
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        $pdo = Database::getInstance()->getConnection();
        // Primeiro, obtém o caminho do ficheiro para poder apagá-lo do servidor
        $stmt_path = $pdo->prepare("SELECT path FROM default_avatars WHERE id = ?");
        $stmt_path->execute([$id]);
        $path = $stmt_path->fetchColumn();

        if ($path && $path !== 'default.png') {
            // Apaga o ficheiro do servidor
            if (file_exists('uploads/avatars/' . $path)) {
                unlink('uploads/avatars/' . $path);
            }
        }
        
        // Apaga o registo do banco de dados
        $stmt_delete = $pdo->prepare("DELETE FROM default_avatars WHERE id = ?");
        return $stmt_delete->execute([$id]);
    }
}
