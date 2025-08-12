<?php
// Proteção: se o utilizador não estiver logado, redireciona para o login.
if (!Auth::isLoggedIn()) {
    header('Location: /login');
    exit();
}

global $url_parts;
// Se a URL for /user, o padrão será 'profile'. Se for /user/payments, a ação será 'payments'.
$action = $url_parts[1] ?? 'profile';

// Carrega o conteúdo da secção apropriada
switch ($action) {
    case 'profile':
        // Toda a lógica que tínhamos antes agora fica dentro deste 'case'
        $current_user_id = Auth::getUserId();
        $user = User::findById($current_user_id);

        $payment_history = [];
        $plan_details = null;
        $user_rooms = [];
        $room_creation_limit = 0; // Limite padrão

        if ($user['account_type'] === 'vip' || $user['account_type'] === 'admin') {
            $payment_history = Payment::getHistoryForUser($current_user_id);
            $plan_details = User::getPlanDetails($current_user_id);
            $user_rooms = Room::getRoomsByUserId($current_user_id);
            
            if ($plan_details && isset($plan_details['plan_id'])) {
                $plan = Plan::findById($plan_details['plan_id']);
                if ($plan) {
                    $room_creation_limit = $plan['room_creation_limit'];
                }
            }
            if ($user['account_type'] === 'admin') {
                $room_creation_limit = 999;
            }
        }

        $success_message = '';
        $errors = [];

        // Mensagens de sucesso via GET (após redirecionamento)
        if (isset($_GET['room_created'])) $success_message = "A sua sala VIP foi criada com sucesso!";
        if (isset($_GET['room_updated'])) $success_message = "A sua sala VIP foi atualizada com sucesso!";
        if (isset($_GET['room_deleted'])) $success_message = "A sua sala VIP foi apagada com sucesso!";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Lógica para desativar a conta
            if (isset($_POST['deactivate_account'])) {
                if (User::deactivate($current_user_id)) {
                    Auth::logout();
                    header('Location: /?deactivated=true');
                    exit();
                } else { $errors[] = "Ocorreu um erro ao desativar a sua conta."; }
            }
            // Lógica para atualizar o avatar
            if (isset($_POST['update_avatar'])) {
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['avatar'];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 2 * 1024 * 1024;
                    if (!in_array($file['type'], $allowed_types)) {
                        $errors[] = "Formato de ficheiro inválido.";
                    } elseif ($file['size'] > $max_size) {
                        $errors[] = "O ficheiro é muito grande.";
                    } else {
                        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $new_filename = 'user_' . $current_user_id . '_' . time() . '.' . $file_extension;
                        $upload_path = 'uploads/avatars/' . $new_filename;
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            if ($user['avatar'] !== 'default.png' && file_exists('uploads/avatars/' . $user['avatar'])) {
                                unlink('uploads/avatars/' . $user['avatar']);
                            }
                            if (User::updateAvatar($current_user_id, $new_filename)) {
                                $success_message = "Avatar atualizado com sucesso!";
                                $user = User::findById($current_user_id);
                            } else { $errors[] = "Erro ao guardar o novo avatar."; }
                        } else { $errors[] = "Ocorreu um erro ao mover o ficheiro."; }
                    }
                } else { $errors[] = "Ocorreu um erro no upload."; }
            }
            
            // Lógica para criar sala VIP
            if (isset($_POST['create_vip_room'])) {
                if ($user['account_type'] === 'vip' || $user['account_type'] === 'admin') {
                    if (count($user_rooms) >= $room_creation_limit) {
                        $errors[] = "Você atingiu o seu limite de {$room_creation_limit} sala(s) criada(s) para o seu plano atual.";
                    } else {
                        $name = trim($_POST['room_name']);
                        $description = trim($_POST['room_description']);
                        $password = $_POST['room_password'];
                        $access_level = $_POST['access_level'];
                        if (empty($name)) {
                            $errors[] = "O nome da sala é obrigatório.";
                        } else {
                            if (Room::create(null, $name, $description, 50, 1024, 'vip', $current_user_id, $password, $access_level)) {
                                header('Location: /user/profile?room_created=true');
                                exit();
                            } else {
                                $errors[] = "Ocorreu um erro ao criar a sua sala. Tente novamente.";
                            }
                        }
                    }
                }
            }

            // Lógica para ATUALIZAR sala VIP
            if (isset($_POST['update_vip_room'])) {
                $room_id = $_POST['room_id'];
                $room_to_edit = Room::findById($room_id);
                if ($room_to_edit && $room_to_edit['created_by_user_id'] == $current_user_id) {
                    $name = trim($_POST['room_name']);
                    if (empty($name)) {
                        $errors[] = "O nome da sala é obrigatório.";
                    } else {
                        if (Room::updateVipRoom($room_id, $name, $_POST['room_description'], $_POST['room_password'], $_POST['access_level'])) {
                            header('Location: /user/profile?room_updated=true');
                            exit();
                        } else { $errors[] = "Ocorreu um erro ao atualizar a sala."; }
                    }
                } else { $errors[] = "Ação não permitida."; }
            }

            // Lógica para APAGAR sala VIP
            if (isset($_POST['delete_vip_room'])) {
                $room_id = $_POST['room_id_to_delete'];
                $room_to_delete = Room::findById($room_id);
                if ($room_to_delete && $room_to_delete['created_by_user_id'] == $current_user_id) {
                    if (Room::delete($room_id)) {
                        header('Location: /user/profile?room_deleted=true');
                        exit();
                    } else { $errors[] = "Ocorreu um erro ao apagar a sala."; }
                } else { $errors[] = "Ação não permitida."; }
            }

            // Lógica para atualizar o perfil
            if (isset($_POST['update_profile'])) {
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $gender = $_POST['gender'] ?? 'none';
                $nickname = trim($_POST['nickname'] ?? '');
                $location = trim($_POST['location'] ?? '');
                $about_me = trim($_POST['about_me'] ?? '');
                $interests = trim($_POST['interests'] ?? '');

                if (empty($name)) $errors[] = "O nome não pode estar vazio.";
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Formato de e-mail inválido.";
                if (!in_array($gender, ['male', 'female', 'none'])) $gender = 'none';
                
                $existingUser = User::findByEmail($email);
                if ($existingUser && $existingUser['id'] != $current_user_id) {
                    $errors[] = "Este e-mail já está a ser utilizado por outra conta.";
                }

                if ($user['account_type'] === 'vip' || $user['account_type'] === 'admin') {
                    if (!empty($nickname)) {
                        $existingNickname = User::findByNickname($nickname);
                        if ($existingNickname && $existingNickname['id'] != $current_user_id) {
                            $errors[] = "Este apelido já está a ser utilizado por outro utilizador.";
                        }
                    }
                }

                if (empty($errors)) {
                    if (User::updateProfile($current_user_id, $name, $email, $gender, $location, $about_me, $interests)) {
                        if ($user['account_type'] === 'vip' || $user['account_type'] === 'admin') {
                            User::updateNickname($current_user_id, $nickname);
                        }
                        $success_message = "Perfil atualizado com sucesso!";
                        $_SESSION['user_name'] = $name;
                        $user = User::findById($current_user_id);
                    } else { $errors[] = "Ocorreu um erro ao atualizar o perfil."; }
                }
            }
            // Lógica para atualizar a senha
            if (isset($_POST['update_password'])) {
                $password = $_POST['password'] ?? '';
                $password_confirm = $_POST['password_confirm'] ?? '';
                if (strlen($password) < 6) $errors[] = "A nova senha deve ter no mínimo 6 caracteres.";
                if ($password !== $password_confirm) $errors[] = "As senhas não coincidem.";
                if (empty($errors)) {
                    if (User::updatePassword($current_user_id, $password)) {
                        $success_message = "Senha atualizada com sucesso!";
                    } else { $errors[] = "Ocorreu um erro ao atualizar a senha."; }
                }
            }
        }
        
        // Verifica se o utilizador quer editar uma sala
        $room_to_edit = null;
        $banned_users = [];
        if (isset($_GET['edit_room'])) {
            $room_id_to_edit = (int)$_GET['edit_room'];
            $room_to_edit = Room::findById($room_id_to_edit);
            if ($room_to_edit && $room_to_edit['created_by_user_id'] == $current_user_id) {
                $banned_users = RoomModeration::getBannedUsersForRoom($room_id_to_edit);
            } else {
                $room_to_edit = null;
            }
        }
        if (isset($_GET['unbanned'])) $success_message = "O banimento foi anulado com sucesso!";

        // Inclui o template da página de perfil
        include 'templates/user/profile.phtml';
        break;

    default:
        // Se a secção não for encontrada, mostra um erro 404
        http_response_code(404);
        include 'pages/404.php';
        break;
}
