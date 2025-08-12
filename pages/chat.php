<?php
if (!Auth::isLoggedIn()) {
    // Este redirect agora funciona, pois nenhuma saída foi gerada.
    header('Location: /login?redirect=chat/room');
    exit();
}

global $url_parts, $view_to_render; // Traz as variáveis globais para este escopo.

$action = $url_parts[1] ?? null;
$slug = $url_parts[2] ?? null;

$user_id = Auth::getUserId();

switch ($action) {
    case 'room':
        if (!$slug) { header('Location: /rooms'); exit(); }

        $room = Room::findBySlug($slug);
        if (!$room) { http_response_code(404); $view_to_render = 'pages/404.php'; break; }

        // Guarda os detalhes da sala na sessão para o AJAX usar
        $_SESSION['current_room_id'] = $room['id'];
        $_SESSION['current_room_char_limit'] = $room['char_limit'];

        if (!isset($_SESSION['chat_ready'][$room['id']])) {
            // Este era o redirect que causava o erro. Agora ele funciona perfeitamente.
            header('Location: /chat/setup/' . $slug);
            exit();
        }

        $sidebar_ad = null;
        if ($_SESSION['user_account_type'] === 'common') {
            $sidebar_ad = Ad::getActiveAdForLocation('chat_sidebar');
        }
        
        // Em vez de incluir, definimos qual template o layout.phtml deve carregar.
        $view_to_render = 'templates/chat/room.phtml';
        break;

    case 'setup':
        if (!$slug) { header('Location: /rooms'); exit(); }
        $room = Room::findBySlug($slug);
        if (!$room) { http_response_code(404); $view_to_render = 'pages/404.php'; break; }

        $user = User::findById($user_id);
        $default_avatars = Avatar::getAll();
        $error_message = $_SESSION['setup_error'] ?? null;
        unset($_SESSION['setup_error']);

        // Define o template a ser renderizado.
        $view_to_render = 'templates/chat/setup.phtml';
        break;

    case 'enter':
        if (!$slug) { header('Location: /rooms'); exit(); }
        $room = Room::findBySlug($slug);
        if (!$room) { http_response_code(404); $view_to_render = 'pages/404.php'; break; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nickname = trim($_POST['nickname'] ?? '');
            
            if (User::isNicknameTaken($nickname, $room['id'], $user_id)) {
                $_SESSION['setup_error'] = "O apelido '".htmlspecialchars($nickname)."' já está em uso nesta sala. Por favor, escolha outro.";
                header('Location: /chat/setup/' . $slug);
                exit();
            }

            $color = $_POST['color'] ?? '#000000';
            if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
                User::updateColor($user_id, $color);
            }
            
            $choose_avatar_option = $_POST['choose_avatar_option'] ?? 'no';
            if ($choose_avatar_option === 'yes') {
                $avatar_path = $_POST['avatar_path'] ?? null;
                if ($avatar_path) {
                    User::updateAvatar($user_id, $avatar_path);
                }
            }

            User::setCurrentRoom($user_id, $room['id']);
            User::setSessionNickname($user_id, $nickname);

            Message::create($user_id, $room['id'], 'entra na sala...', 'system');
            $_SESSION['chat_ready'][$room['id']] = true;
            header('Location: /chat/room/' . $slug);
            exit();
        }
        header('Location: /chat/setup/' . $slug);
        exit();

    default:
        header('Location: /rooms');
        exit();
}
