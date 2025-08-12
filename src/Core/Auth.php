<?php
/**
 * Classe de Autenticação
 *
 * Gere o estado de login, sessões e permissões do utilizador.
 * Os métodos são estáticos para serem facilmente acessíveis em todo o projeto.
 */
class Auth {

    /**
     * Inicia a sessão para um utilizador após o login bem-sucedido.
     * @param array $user - O array de dados do utilizador vindo do banco de dados.
     */
    public static function login($user) {
        // Regenera o ID da sessão para prevenir session fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_account_type'] = $user['account_type'];
    }

    /**
     * Termina a sessão do utilizador (logout).
     */
    public static function logout() {
        // Limpa todas as variáveis da sessão
        $_SESSION = array();

        // Apaga o cookie da sessão
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Finalmente, destrói a sessão.
        session_destroy();
    }

    /**
     * Verifica se o utilizador está atualmente logado.
     * @return bool - True se logado, false caso contrário.
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Obtém o nome do utilizador logado.
     * @return string|null - O nome do utilizador ou null se não estiver logado.
     */
    public static function getUserName() {
        return self::isLoggedIn() ? $_SESSION['user_name'] : null;
    }

    /**
     * Obtém o ID do utilizador logado.
     * @return int|null - O ID do utilizador ou null se não estiver logado.
     */
    public static function getUserId() {
        return self::isLoggedIn() ? $_SESSION['user_id'] : null;
    }
}
?>
