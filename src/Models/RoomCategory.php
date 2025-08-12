<?php
/**
 * Modelo RoomCategory
 *
 * Responsável pelas operações na tabela de categorias de salas.
 */
class RoomCategory {

    /**
     * Obtém todas as categorias de salas, ordenadas.
     * @return array
     */
    public static function getAll() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM room_categories ORDER BY display_order ASC, name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Encontra uma categoria pelo seu ID.
     * @param int $id
     * @return mixed
     */
    public static function findById($id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM room_categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Cria uma nova categoria de sala.
     * @param string $name, $description, $icon_svg
     * @param int $display_order
     * @return bool
     */
    public static function create($name, $description, $icon_svg, $display_order) {
        $pdo = Database::getInstance()->getConnection();
        $slug = create_slug($name);
        $sql = "INSERT INTO room_categories (name, slug, description, icon_svg, display_order) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$name, $slug, $description, $icon_svg, $display_order]);
    }

    /**
     * Atualiza os dados de uma categoria.
     * @param int $id
     * @param string $name, $description, $icon_svg
     * @param int $display_order
     * @return bool
     */
    public static function update($id, $name, $description, $icon_svg, $display_order) {
        $pdo = Database::getInstance()->getConnection();
        $slug = create_slug($name);
        $sql = "UPDATE room_categories SET name = ?, slug = ?, description = ?, icon_svg = ?, display_order = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$name, $slug, $description, $icon_svg, $display_order, $id]);
    }

    /**
     * Apaga uma categoria.
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("DELETE FROM room_categories WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
