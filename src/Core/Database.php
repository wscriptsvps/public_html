<?php
/**
 * Classe de Conexão com o Banco de Dados
 *
 * Utiliza o padrão Singleton para garantir que existe apenas uma conexão
 * com o banco de dados durante todo o pedido, otimizando recursos.
 * Usa PDO para segurança contra SQL Injection.
 */
class Database {
    // Propriedades da conexão
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;

    // Instância da classe (para o padrão Singleton)
    private static $instance = null;
    
    // Conexão PDO
    public $conn;

    /**
     * O construtor é privado para não permitir a criação de instâncias
     * diretas da classe.
     */
    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Em caso de erro, termina a execução e mostra uma mensagem genérica
            // Em produção, pode querer registar o erro num ficheiro de log.
            die("Erro de conexão com o banco de dados: " . $e->getMessage());
        }
    }

    /**
     * Método estático que controla o acesso à instância.
     * Esta é a forma de obter a conexão com o banco de dados.
     *
     * @return Database A instância única da classe Database.
     */
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Método para obter a conexão PDO diretamente.
     *
     * @return PDO A conexão PDO ativa.
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Previne a clonagem da instância.
     */
    private function __clone() {}

    /**
     * Previne a desserialização da instância.
     */
    public function __wakeup() {}
}
?>
