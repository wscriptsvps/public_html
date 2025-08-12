<?php
/**
 * Modelo User
 *
 * Responsável por todas as operações do banco de dados relacionadas a utilizadores.
 */
class User {

    /**
     * Verifica se já existe alguma conta de administrador.
     * @return bool
     */
    public static function hasAdminAccount() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT COUNT(id) FROM users WHERE account_type = 'admin'");
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Conta todos os utilizadores ativos.
     * @return int
     */
    public static function countAll() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT COUNT(id) FROM users WHERE status = 'active'");
        return $stmt->fetchColumn();
    }

    /**
     * Obtém a contagem de novos registos por dia nos últimos 30 dias.
     * @return array
     */
    public static function getRegistrationsByDay() {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT DATE(created_at) as registration_date, COUNT(id) as count
                FROM users
                WHERE created_at >= CURDATE() - INTERVAL 30 DAY
                GROUP BY DATE(created_at)
                ORDER BY registration_date ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtém todos os utilizadores, exceto os que foram apagados.
     * @return array
     */
    public static function getAll() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT id, name, email, account_type, status, last_ip, created_at 
                             FROM users 
                             WHERE status != 'deleted' 
                             ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    /**
     * Encontra um utilizador pelo seu ID.
     * @param int $id
     * @return mixed
     */
    public static function findById($id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT id, name, nickname, session_nickname, email, avatar, color, gender, account_type, status FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Encontra um utilizador pelo seu endereço de e-mail.
     * @param string $email
     * @return mixed
     */
    public static function findByEmail($email) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Encontra um utilizador pelo seu apelido (nickname) VIP.
     * @param string $nickname
     * @return mixed
     */
    public static function findByNickname($nickname) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE nickname = ?");
        $stmt->execute([$nickname]);
        return $stmt->fetch();
    }

    /**
     * Verifica se um apelido (VIP ou de sessão) já está em uso por outro utilizador ativo numa sala.
     * @param string $nickname
     * @param int $room_id
     * @param int $exclude_user_id - O ID do utilizador atual, para não se verificar a si mesmo.
     * @return bool
     */
    public static function isNicknameTaken($nickname, $room_id, $exclude_user_id) {
        $pdo = Database::getInstance()->getConnection();
        // Verifica se o apelido corresponde a um nickname VIP de outro utilizador
        // OU a um session_nickname de outro utilizador na mesma sala.
        $sql = "SELECT id FROM users 
                WHERE (nickname = ? OR (session_nickname = ? AND current_room_id = ?))
                AND id != ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nickname, $nickname, $room_id, $exclude_user_id]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Cria um novo utilizador no banco de dados.
     * @param string $name, $email, $password, $gender
     * @param string $account_type
     * @return mixed
     */
    public static function create($name, $email, $password, $gender = 'none', $account_type = 'common') {
        $pdo = Database::getInstance()->getConnection();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $uuid = bin2hex(random_bytes(18));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        $default_avatar = Avatar::getRandom();

        $stmt = $pdo->prepare(
            "INSERT INTO users (uuid, name, email, password, avatar, gender, account_type, last_ip, created_at, last_activity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        if ($stmt->execute([$uuid, $name, $email, $hashed_password, $default_avatar, $gender, $account_type, $ip])) {
            $id = $pdo->lastInsertId();
            return ['id' => $id, 'name' => $name, 'email' => $email, 'account_type' => $account_type];
        }
        return false;
    }

    /**
     * Cria utilizadores "bot" em massa para uma sala específica.
     * @param int $count - A quantidade de bots a criar.
     * @param int $room_id - O ID da sala onde os bots serão adicionados.
     * @return int - O número de bots criados com sucesso.
     */
    public static function createBots($count, $room_id) {
        $pdo = Database::getInstance()->getConnection();
        $created_count = 0;
        // Lista de nomes base para gerar variedade
        $bot_names = ['Ana', 'Bruno', 'Carla', 'Daniel', 'Elisa', 'Fábio', 'Gisele', 'Hugo', 'Inês', 'João', 'Lia', 'Marcos', 'Nádia', 'Otávio', 'Patrícia', 'Sofia', 'Tiago', 'Vera'];

        for ($i = 0; $i < $count; $i++) {
            $name = $bot_names[array_rand($bot_names)] . rand(10, 99);
            $email = 'bot_' . strtolower($name) . '_' . bin2hex(random_bytes(4)) . '@bot.localhost';
            $password = bin2hex(random_bytes(16));
            
            if (self::create($name, $email, $password, 'none', 'bot')) {
                $bot_id = $pdo->lastInsertId();
                // Associa o bot à sala e atualiza a sua atividade para que apareça online
                self::setCurrentRoom($bot_id, $room_id);
                self::updateActivity($bot_id);
                $created_count++;
            }
        }
        return $created_count;
    }

    /**
     * Apaga permanentemente todos os utilizadores do tipo "bot".
     * @return bool
     */
    public static function deleteAllBots() {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("DELETE FROM users WHERE account_type = 'bot'");
        return $stmt->execute();
    }

    /**
     * Atualiza os dados de um utilizador a partir do painel de administração.
     * @param int $id, string $name, string $email, string $account_type, string $status
     * @return bool
     */
    public static function updateUserFromAdmin($id, $name, $email, $account_type, $status) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "UPDATE users SET name = ?, email = ?, account_type = ?, status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$name, $email, $account_type, $status, $id]);
    }

    /**
     * Atualiza os dados do perfil de um utilizador.
     * @param int $id, string $name, string $email, string $gender, string $location, string $about_me, string $interests
     * @return bool
     */
    public static function updateProfile($id, $name, $email, $gender, $location, $about_me, $interests) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, gender = ?, location = ?, about_me = ?, interests = ? WHERE id = ?");
        return $stmt->execute([$name, $email, $gender, $location, $about_me, $interests, $id]);
    }
    
    /**
     * Atualiza o apelido exclusivo de um utilizador VIP.
     * @param int $id
     * @param string $nickname
     * @return bool
     */
    public static function updateNickname($id, $nickname) {
        $pdo = Database::getInstance()->getConnection();
        // Define o nickname como NULL se estiver vazio, para não violar a constraint UNIQUE
        $nickname = empty($nickname) ? null : $nickname;
        $stmt = $pdo->prepare("UPDATE users SET nickname = ? WHERE id = ?");
        return $stmt->execute([$nickname, $id]);
    }
    
    /**
     * Define o apelido temporário de sessão de um utilizador.
     * @param int $user_id
     * @param string $nickname
     * @return bool
     */
    public static function setSessionNickname($user_id, $nickname) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE users SET session_nickname = ? WHERE id = ?");
        return $stmt->execute([$nickname, $user_id]);
    }

    /**
     * Limpa o apelido de sessão de um utilizador.
     * @param int $user_id
     * @return bool
     */
    public static function clearSessionNickname($user_id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE users SET session_nickname = NULL WHERE id = ?");
        return $stmt->execute([$user_id]);
    }

    /**
     * Atualiza a cor do nome de um utilizador.
     * @param int $id
     * @param string $color
     * @return bool
     */
    public static function updateColor($id, $color) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE users SET color = ? WHERE id = ?");
        return $stmt->execute([$color, $id]);
    }

    /**
     * Atualiza a senha de um utilizador.
     * @param int $id, string $newPassword
     * @return bool
     */
    public static function updatePassword($id, $newPassword) {
        $pdo = Database::getInstance()->getConnection();
        $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashed_password, $id]);
    }

    /**
     * Atualiza o caminho do avatar de um utilizador.
     * @param int $id, string $avatarPath
     * @return bool
     */
    public static function updateAvatar($id, $avatarPath) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        return $stmt->execute([$avatarPath, $id]);
    }

    /**
     * Atualiza o status da conta de um utilizador.
     * @param int $id
     * @param string $status ('active', 'suspended', 'banned', 'deleted')
     * @return bool
     */
    public static function updateStatus($id, $status) {
        $valid_statuses = ['active', 'suspended', 'banned', 'deleted'];
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Desativa a conta de um utilizador (alias para updateStatus).
     * @param int $id
     * @return bool
     */
    public static function deactivate($id) {
        return self::updateStatus($id, 'deleted');
    }

    /**
     * Atualiza o timestamp da última atividade do utilizador.
     * @param int $user_id
     */
    public static function updateActivity($user_id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    }

    /**
     * Obtém uma lista de todos os utilizadores considerados online no site inteiro.
     * @return array
     */
    public static function getOnlineUsers() {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT id, name, nickname, avatar, gender, account_type FROM users 
                WHERE last_activity > NOW() - INTERVAL 30 SECOND
                ORDER BY name ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtém uma lista de utilizadores online numa sala específica.
     * @param int $room_id
     * @return array
     */
    public static function getOnlineUsersInRoom($room_id) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT id, name, nickname, session_nickname, avatar, gender, account_type FROM users 
                WHERE last_activity > NOW() - INTERVAL 30 SECOND
                AND current_room_id = ?
                ORDER BY name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$room_id]);
        return $stmt->fetchAll();
    }

    /**
     * Atualiza a conta de um utilizador para VIP.
     * @param int $id
     * @param int $plan_id
     * @param int $interval_months
     * @return bool
     */
    public static function upgradeToVip($id, $plan_id, $interval_months) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "UPDATE users SET account_type = 'vip', plan_id = ?, plan_expires_at = DATE_ADD(NOW(), INTERVAL ? MONTH) WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$plan_id, $interval_months, $id]);
    }
    
    /**
     * Obtém os detalhes do plano de um utilizador, como o nome e a data de expiração.
     * @param int $user_id
     * @return mixed
     */
    public static function getPlanDetails($user_id) {
        $pdo = Database::getInstance()->getConnection();
        $sql = "SELECT u.plan_expires_at, p.name as plan_name, p.id as plan_id
                FROM users u
                LEFT JOIN plans p ON u.plan_id = p.id
                WHERE u.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }

    /**
     * Gera e guarda um código de 6 dígitos para redefinição de senha.
     * @param string $email
     * @return string|false O código gerado ou false em caso de falha.
     */
    public static function generatePasswordResetToken($email) {
        $pdo = Database::getInstance()->getConnection();
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE email = ?");
        if ($stmt->execute([$code, $expires_at, $email])) {
            return $code;
        }
        return false;
    }

    /**
     * Encontra um utilizador por um e-mail e um código de redefinição válidos.
     * @param string $email
     * @param string $code
     * @return mixed
     */
    public static function findByEmailAndResetCode($email, $code) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND reset_token = ? AND reset_token_expires_at > NOW()");
        $stmt->execute([$email, $code]);
        return $stmt->fetch();
    }

    /**
     * Redefine a senha de um utilizador e invalida o token.
     * @param int $id
     * @param string $new_password
     * @return bool
     */
    public static function resetPassword($id, $new_password) {
        $pdo = Database::getInstance()->getConnection();
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
        return $stmt->execute([$hashed_password, $id]);
    }

    /**
     * Define a sala atual de um utilizador.
     * @param int $user_id
     * @param int $room_id
     * @return bool
     */
    public static function setCurrentRoom($user_id, $room_id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE users SET current_room_id = ? WHERE id = ?");
        return $stmt->execute([$room_id, $user_id]);
    }

    /**
     * Limpa a sala atual de um utilizador (define como NULL).
     * @param int $user_id
     * @return bool
     */
    public static function clearCurrentRoom($user_id) {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE users SET current_room_id = NULL WHERE id = ?");
        return $stmt->execute([$user_id]);
    }
}
