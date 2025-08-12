<?php
/**
 * Ponto de Entrada Principal (Roteador)
 */

// DEBUG: Manter para exibir erros durante o desenvolvimento.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inicia a sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Carregar configurações e dependências essenciais
require_once 'vendor/autoload.php';
require_once 'src/config.php';
require_once 'src/Core/Database.php';
require_once 'src/Core/Auth.php';
require_once 'src/Helpers/Functions.php';
require_once 'src/Helpers/FileManager.php';
require_once 'src/Helpers/Mail.php';
require_once 'src/Models/User.php';
require_once 'src/Models/Message.php';
require_once 'src/Models/Room.php';
require_once 'src/Models/Plan.php';
require_once 'src/Models/Payment.php';
require_once 'src/Models/Ad.php';
require_once 'src/Models/Contact.php';
require_once 'src/Models/Avatar.php';
require_once 'src/Models/Report.php';
require_once 'src/Models/BlockedWord.php';
require_once 'src/Models/InfractionLog.php';
require_once 'src/Models/BotMessage.php';
require_once 'src/Models/RoomCategory.php';
require_once 'src/Models/RoomModeration.php';
require_once 'src/Core/Filter.php';

// 2. Lógica do Roteador
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : 'home';
$url_parts = explode('/', $url);
$page_name = ($url_parts[0] === '' || $url_parts[0] === 'index.php') ? 'home' : $url_parts[0];

// --- Lógica de Configuração Inicial do Administrador ---
$has_admin = User::hasAdminAccount();
if ($page_name !== 'create-admin' && !$has_admin) {
    header('Location: /create-admin');
    exit();
}

// --- Proteção de Rotas ---
if (($page_name === 'chat' || $page_name === 'user' || strpos($page_name, 'admin') === 0) && !Auth::isLoggedIn()) {
    header('Location: /login');
    exit();
}

// 3. Lógica de Roteamento Principal
$view_to_render = null;

// Lista de páginas que são "controladores" com lógica complexa (ex: processam formulários).
// Estes arquivos são executados e são responsáveis por definir a sua própria variável $view_to_render.
$logic_controllers = ['chat', 'user', 'admin', 'login', 'register', 'logout', 'create-admin'];

if (in_array($page_name, $logic_controllers)) {
    // É um controlador de lógica, então incluímos o arquivo para ser executado.
    $logic_path = "pages/{$page_name}.php";
    if (file_exists($logic_path)) {
        include $logic_path;
    } else {
        // Se o controlador esperado não existe, é um erro 404.
        http_response_code(404);
        $view_to_render = 'pages/404.php';
    }
} else {
    // Para todas as outras páginas, assumimos que são páginas de conteúdo simples.
    // Apenas definimos o caminho do arquivo de view para ser incluído pelo layout.
    $view_path = "pages/{$page_name}.php";
    if (file_exists($view_path)) {
        $view_to_render = $view_path;
    } else {
        http_response_code(404);
        $view_to_render = 'pages/404.php';
    }
}

// 4. Incluir o layout principal que "embrulha" todo o site.
// Esta linha só é alcançada se nenhum controlador tiver chamado exit().
// A variável $view_to_render deve estar definida neste ponto.
include 'templates/layout.phtml';
?>
