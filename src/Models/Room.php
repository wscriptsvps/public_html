<?php
/**
 * Modelo Room
 *
 * Responsável por todas as operações do banco de dados relacionadas a salas de chat.
 */
class Room {

    /**
     * Obtém todas as salas de chat, incluindo a contagem de utilizadores online em cada uma.
     * @return array - Uma lista de todas as salas.
     */
    public static function getAll() {
        $pdo = Database::getInstance()->getConnection();
        // A consulta foi atualizada para juntar a tabela de utilizadores e contar quantos estão ativos em cada sala.
        $sql = "SELECT r.*, COUNT(u.id) as user_count
                FROM rooms r
                LEFT JOIN users u ON r.id = u.current_room_id AND u.last_activity > NOW() - INTERVAL 30 SECOND
                GROUP BY r.id
                ORDER BY r.id DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Encontra uma sala pelo seu ID.
     * @param int $id
     * @return mixed
     */
    public static function findById($id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Encontra uma sala pelo seu slug.
     * @param string $slug
     * @return mixed
     */
    public static function findBySlug($slug) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE slug = ? AND status = 'active'");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    /**
     * Cria uma nova sala de chat.
     * @param int|null $category_id
     * @param string $name, $description
     * @param int $user_limit, $char_limit
     * @param string $type
     * @param int|null $created_by_user_id
     * @param string|null $password
     * @param string $access_level
     * @return bool
     */
    public static function create($category_id, $name, $description, $user_limit, $char_limit, $type, $created_by_user_id = null, $password = null, $access_level = 'vip') {
        $pdo = Database::getInstance()->getConnection();
        $slug = create_slug($name);
        
        $original_slug = $slug;
        $counter = 1;
        while (self::findBySlug($slug)) {
            $slug = $original_slug . '-' . $counter++;
        }

        $hashed_password = $password ? password_hash($password, PASSWORD_DEFAULT) : null;

        $sql = "INSERT INTO rooms (category_id, name, slug, description, user_limit, char_limit, password, access_level, type, status, created_by_user_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW())";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$category_id, $name, $slug, $description, $user_limit, $char_limit, $hashed_password, $access_level, $type, $created_by_user_id]);
    }

    /**
     * Atualiza os dados de uma sala de chat (versão do admin).
     * @param int $id, $category_id
     * @param string $name, $description
     * @param int $user_limit, $char_limit
     * @param string $type, $status
     * @return bool
     */
    public static function update($id, $category_id, $name, $description, $user_limit, $char_limit, $type, $status) {
        $pdo = Database::getInstance()->getConnection();
        $slug = create_slug($name);

        $sql = "UPDATE rooms SET category_id = ?, name = ?, slug = ?, description = ?, user_limit = ?, char_limit = ?, type = ?, status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$category_id, $name, $slug, $description, $user_limit, $char_limit, $type, $status, $id]);
    }

    /**
     * Atualiza os dados de uma sala de chat criada por um VIP.
     * @param int $id
     * @param string $name, $description
     * @param string|null $password
     * @param string $access_level
     * @return bool
     */
    public static function updateVipRoom($id, $name, $description, $password, $access_level) {
        $pdo = Database::getInstance()->getConnection();
        $slug = create_slug($name);
        
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE rooms SET name = ?, slug = ?, description = ?, password = ?, access_level = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$name, $slug, $description, $hashed_password, $access_level, $id]);
        } else {
            // Se a senha estiver vazia, não a atualiza no banco de dados
            $sql = "UPDATE rooms SET name = ?, slug = ?, description = ?, access_level = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$name, $slug, $description, $access_level, $id]);
        }
    }

    /**
     * Apaga uma sala de chat.
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Conta todas as salas ativas.
     * @return int
     */
    public static function countAll() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT COUNT(id) FROM rooms WHERE status = 'active'");
        return $stmt->fetchColumn();
    }

    /**
     * Obtém as 4 salas mais populares com base no número de utilizadores.
     * @return array
     */
    public static function getPopularRooms() {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT r.*, COUNT(u.id) as user_count
                FROM rooms r
                LEFT JOIN users u ON r.id = u.current_room_id
                WHERE r.status = 'active'
                GROUP BY r.id
                ORDER BY user_count DESC, r.name ASC
                LIMIT 4";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtém todas as salas criadas por um utilizador específico.
     * @param int $user_id
     * @return array
     */
    public static function getRoomsByUserId($user_id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE created_by_user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Conta o número de salas criadas por um utilizador específico.
     * @param int $user_id
     * @return int
     */
    public static function countRoomsByUserId($user_id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(id) FROM rooms WHERE created_by_user_id = ?");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Obtém apenas as salas criadas por administradores (Salas Gerais).
     * @return array
     */
    public static function getGeneralRooms() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM rooms WHERE created_by_user_id IS NULL ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    /**
     * Obtém todas as salas criadas por utilizadores (assinantes).
     * @return array
     */
    public static function getRoomsBySubscribers() {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT r.*, u.name as creator_name
                FROM rooms r
                JOIN users u ON r.created_by_user_id = u.id
                WHERE r.created_by_user_id IS NOT NULL
                ORDER BY r.created_at DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }
}
