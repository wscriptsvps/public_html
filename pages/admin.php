<?php
// Proteção de Rota: Verifica se o utilizador está logado e se é administrador.
if (!Auth::isLoggedIn() || $_SESSION['user_account_type'] !== 'admin') {
    header('Location: /');
    exit();
}

global $url_parts;
$section = $url_parts[1] ?? 'dashboard';
$action = $url_parts[2] ?? 'list';
$id = $url_parts[3] ?? null;

// Ação de Download (precisa de ser processada antes de qualquer output HTML)
if ($section === 'file-manager' && $action === 'download') {
    $path_parts = array_slice($url_parts, 3);
    $path = implode('/', $path_parts);
    $full_path = FileManager::getSafePath($path);
    if ($full_path && is_file($full_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($full_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($full_path));
        readfile($full_path);
        exit;
    }
}

$success_message = '';
$errors = [];

// Processa os formulários quando enviados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ações para Utilizadores
    if (isset($_POST['update_user_status'])) {
        $user_id = $_POST['user_id'];
        $new_status = $_POST['new_status'];
        if (User::updateStatus($user_id, $new_status)) {
            $success_message = "Status do utilizador #{$user_id} foi alterado para '{$new_status}' com sucesso!";
        } else { $errors[] = "Ocorreu um erro ao alterar o status do utilizador."; }
    }
    if (isset($_POST['edit_user_submit'])) {
        $user_id = $_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $account_type = $_POST['account_type'];
        $status = $_POST['status'];
        if (User::updateUserFromAdmin($user_id, $name, $email, $account_type, $status)) {
            $success_message = "Utilizador atualizado com sucesso!";
        } else { $errors[] = "Ocorreu um erro ao atualizar o utilizador."; }
    }

    // Ações para Salas de Chat
    if (isset($_POST['save_room'])) {
        $room_id = $_POST['room_id'] ?? null;
        $category_id = $_POST['category_id'] ?? null;
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $user_limit = (int)$_POST['user_limit'];
        $char_limit = (int)$_POST['char_limit'];
        $type = $_POST['type'];
        $status = $_POST['status'] ?? 'active';
        
        $category_id = empty($category_id) ? null : (int)$category_id;

        if (empty($name) || $user_limit <= 0 || $char_limit <= 0) {
            $errors[] = "Nome, Limite de Utilizadores e Limite de Caracteres são obrigatórios e devem ser maiores que zero.";
        } else {
            if ($room_id) {
                if (Room::update($room_id, $category_id, $name, $description, $user_limit, $char_limit, $type, $status)) {
                    $success_message = "Sala atualizada com sucesso!";
                } else { $errors[] = "Erro ao atualizar a sala."; }
            } else {
                if (Room::create($category_id, $name, $description, $user_limit, $char_limit, $type)) {
                    $success_message = "Sala criada com sucesso!";
                } else { $errors[] = "Erro ao criar a sala."; }
            }
        }
    }
    if (isset($_POST['delete_room'])) {
        $room_id = $_POST['room_id'];
        if (Room::delete($room_id)) {
            $success_message = "Sala #{$room_id} foi apagada com sucesso!";
        } else { $errors[] = "Erro ao apagar a sala."; }
    }

    // Ações para Planos de Assinatura
    if (isset($_POST['save_plan'])) {
        $plan_id = $_POST['plan_id'] ?? null;
        $name = trim($_POST['name']);
        $price = (float)str_replace(',', '.', $_POST['price']);
        $interval_months = (int)$_POST['interval_months'];
        $room_creation_limit = (int)$_POST['room_creation_limit'];
        $status = $_POST['status'] ?? 'active';

        if (empty($name) || $price <= 0 || $interval_months <= 0 || $room_creation_limit < 0) {
            $errors[] = "Todos os campos são obrigatórios e os valores numéricos devem ser positivos.";
        } else {
            if ($plan_id) { // Atualizar plano
                if (Plan::update($plan_id, $name, $price, $interval_months, $room_creation_limit, $status)) {
                    $success_message = "Plano atualizado com sucesso!";
                } else { $errors[] = "Erro ao atualizar o plano."; }
            } else { // Criar novo plano
                if (Plan::create($name, $price, $interval_months, $room_creation_limit)) {
                    $success_message = "Plano criado com sucesso!";
                } else { $errors[] = "Erro ao criar o plano."; }
            }
        }
    }
    if (isset($_POST['delete_plan'])) {
        $plan_id = $_POST['plan_id'];
        if (Plan::delete($plan_id)) {
            $success_message = "Plano #{$plan_id} foi apagado com sucesso!";
        } else { $errors[] = "Erro ao apagar o plano."; }
    }

    // Ações para Anúncios
    if (isset($_POST['save_ad'])) {
        $ad_id = $_POST['ad_id'] ?? null;
        $title = trim($_POST['title']);
        $content_html = trim($_POST['content_html']);
        $display_location = $_POST['display_location'];
        $status = $_POST['status'] ?? 'active';
        if (empty($title) || empty($content_html)) {
            $errors[] = "Título e Conteúdo HTML são obrigatórios.";
        } else {
            if ($ad_id) {
                if (Ad::update($ad_id, $title, $content_html, $display_location, $status)) {
                    $success_message = "Anúncio atualizado com sucesso!";
                } else { $errors[] = "Erro ao atualizar o anúncio."; }
            } else {
                if (Ad::create($title, $content_html, $display_location)) {
                    $success_message = "Anúncio criado com sucesso!";
                } else { $errors[] = "Erro ao criar o anúncio."; }
            }
        }
    }
    if (isset($_POST['delete_ad'])) {
        $ad_id = $_POST['ad_id'];
        if (Ad::delete($ad_id)) {
            $success_message = "Anúncio #{$ad_id} foi apagado com sucesso!";
        } else { $errors[] = "Erro ao apagar o anúncio."; }
    }

    // Ações para Contatos
    if (isset($_POST['update_contact_status'])) {
        $contact_id = $_POST['contact_id'];
        $new_status = $_POST['new_status'];
        if (Contact::updateStatus($contact_id, $new_status)) {
            $success_message = "Status da mensagem #{$contact_id} atualizado.";
        } else { $errors[] = "Erro ao atualizar o status da mensagem."; }
    }
    if (isset($_POST['delete_contact'])) {
        $contact_id = $_POST['contact_id'];
        if (Contact::delete($contact_id)) {
            header('Location: /admin/contacts?deleted=true');
            exit();
        } else { $errors[] = "Erro ao apagar a mensagem."; }
    }

    // Ações para Avatares Padrão
    if (isset($_POST['upload_avatar'])) {
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar_file'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = "Formato de ficheiro inválido. Apenas JPG, PNG, GIF e WEBP são permitidos.";
            } else {
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = 'default_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension;
                $upload_path = 'uploads/avatars/' . $new_filename;
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    if (Avatar::create($new_filename)) {
                        $success_message = "Avatar padrão enviado com sucesso!";
                    } else { $errors[] = "Erro ao guardar o avatar na base de dados."; }
                } else { $errors[] = "Erro ao mover o ficheiro."; }
            }
        } else { $errors[] = "Ocorreu um erro no upload."; }
    }
    if (isset($_POST['delete_avatar'])) {
        $avatar_id = $_POST['avatar_id'];
        if (Avatar::delete($avatar_id)) {
            $success_message = "Avatar padrão apagado com sucesso!";
        } else { $errors[] = "Erro ao apagar o avatar."; }
    }

    // Ações para Denúncias
    if (isset($_POST['update_report_status'])) {
        $report_id = $_POST['report_id'];
        $new_status = $_POST['new_status'];
        if (Report::updateStatus($report_id, $new_status)) {
            $success_message = "Status da denúncia #{$report_id} atualizado.";
        } else { $errors[] = "Erro ao atualizar o status da denúncia."; }
    }

    // Ações para Moderação
    if (isset($_POST['add_blocked_word'])) {
        $word = trim($_POST['word']);
        if (!empty($word)) {
            if (BlockedWord::create($word)) {
                $success_message = "Palavra '{$word}' adicionada à lista de bloqueio.";
            } else { $errors[] = "Erro ao adicionar a palavra (talvez já exista)."; }
        } else { $errors[] = "O campo da palavra não pode estar vazio."; }
    }
    if (isset($_POST['delete_blocked_word'])) {
        $word_id = $_POST['word_id'];
        if (BlockedWord::delete($word_id)) {
            $success_message = "Palavra removida da lista de bloqueio.";
        } else { $errors[] = "Erro ao remover a palavra."; }
    }

    // Ações para o Gerenciador de Arquivos
    if (isset($_POST['save_file_content'])) {
        $path = $_POST['path'];
        $content = $_POST['content'];
        if (FileManager::saveFileContent($path, $content)) {
            $success_message = "Ficheiro '".basename($path)."' guardado com sucesso!";
        } else { $errors[] = "Erro ao guardar o ficheiro '".basename($path)."'. Verifique as permissões."; }
    }
    if (isset($_POST['upload_file'])) {
        if (isset($_FILES['file_to_upload']) && $_FILES['file_to_upload']['error'] === UPLOAD_ERR_OK) {
            if (FileManager::uploadFile($_FILES['file_to_upload'], $_POST['path'])) {
                $success_message = "Ficheiro enviado com sucesso!";
            } else { $errors[] = "Erro ao enviar o ficheiro. Verifique as permissões."; }
        } else { $errors[] = "Ocorreu um erro no upload."; }
    }
    if (isset($_POST['create_file'])) {
        $path = $_POST['path'] . '/' . $_POST['file_name'];
        if (FileManager::createFile($path)) {
            $success_message = "Ficheiro criado com sucesso!";
        } else { $errors[] = "Erro ao criar o ficheiro (talvez já exista)."; }
    }
    if (isset($_POST['create_folder'])) {
        $path = $_POST['path'] . '/' . $_POST['folder_name'];
        if (FileManager::createFolder($path)) {
            $success_message = "Pasta criada com sucesso!";
        } else { $errors[] = "Erro ao criar a pasta (talvez já exista)."; }
    }
    if (isset($_POST['rename_item'])) {
        $old_path = $_POST['old_path'];
        $new_name = $_POST['new_name'];
        if (FileManager::rename($old_path, $new_name)) {
            $success_message = "Item renomeado com sucesso!";
        } else { $errors[] = "Erro ao renomear o item."; }
    }
    if (isset($_POST['delete_file'])) {
        $path = $_POST['path'];
        if (FileManager::deleteFile($path)) {
            $success_message = "Ficheiro '".basename($path)."' apagado com sucesso!";
        } else {
            $errors[] = "Erro ao apagar o ficheiro '".basename($path)."'. Verifique as permissões.";
        }
    }
    if (isset($_POST['delete_folder'])) {
        $path = $_POST['path'];
        if (FileManager::deleteFolder($path)) {
            $success_message = "Pasta '".basename($path)."' apagada com sucesso!";
        } else {
            $errors[] = "Erro ao apagar a pasta '".basename($path)."'. Verifique as permissões ou se a pasta não está vazia.";
        }
    }

    // Ações para Bots
    if (isset($_POST['create_bots'])) {
        $room_id = (int)($_POST['room_id'] ?? 0);
        $bot_count = (int)($_POST['bot_count'] ?? 0);

        if ($room_id > 0 && $bot_count > 0) {
            $created_count = User::createBots($bot_count, $room_id);
            if ($created_count > 0) {
                $success_message = "{$created_count} bots foram criados e adicionados à sala com sucesso!";
            } else {
                $errors[] = "Nenhum bot foi criado. Ocorreu um erro.";
            }
        } else {
            $errors[] = "Por favor, selecione uma sala e uma quantidade válida de bots.";
        }
    }
    if (isset($_POST['delete_all_bots'])) {
        if (User::deleteAllBots()) {
            $success_message = "Todos os bots foram apagados com sucesso!";
        } else {
            $errors[] = "Ocorreu um erro ao apagar os bots.";
        }
    }
    
    // Ações para Mensagens de Bots
    if (isset($_POST['add_bot_message'])) {
        $message_text = trim($_POST['message_text']);
        if (!empty($message_text)) {
            if (BotMessage::create($message_text)) {
                $success_message = "Mensagem de bot adicionada com sucesso!";
            } else { $errors[] = "Erro ao adicionar a mensagem."; }
        } else { $errors[] = "O campo da mensagem não pode estar vazio."; }
    }
    if (isset($_POST['delete_bot_message'])) {
        $message_id = $_POST['message_id'];
        if (BotMessage::delete($message_id)) {
            $success_message = "Mensagem de bot removida com sucesso!";
        } else { $errors[] = "Erro ao remover a mensagem."; }
    }

    // Ações para Categorias de Salas
    if (isset($_POST['save_category'])) {
        $category_id = $_POST['category_id'] ?? null;
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $icon_svg = trim($_POST['icon_svg']);
        $display_order = (int)$_POST['display_order'];

        if (empty($name)) {
            $errors[] = "O nome da categoria é obrigatório.";
        } else {
            if ($category_id) { // Atualizar
                if (RoomCategory::update($category_id, $name, $description, $icon_svg, $display_order)) {
                    $success_message = "Categoria atualizada com sucesso!";
                } else { $errors[] = "Erro ao atualizar a categoria."; }
            } else { // Criar
                if (RoomCategory::create($name, $description, $icon_svg, $display_order)) {
                    $success_message = "Categoria criada com sucesso!";
                } else { $errors[] = "Erro ao criar a categoria."; }
            }
        }
    }
    if (isset($_POST['delete_category'])) {
        $category_id = $_POST['category_id'];
        if (RoomCategory::delete($category_id)) {
            $success_message = "Categoria apagada com sucesso!";
        } else { $errors[] = "Erro ao apagar a categoria. Verifique se não existem salas associadas a ela."; }
    }
}

// Mensagem de sucesso para o caso de deleção de contato
if (isset($_GET['deleted']) && $_GET['deleted'] == 'true') {
    $success_message = "Mensagem apagada com sucesso!";
}

?>

<div class="flex">
    <!-- Barra de Navegação Lateral do Admin -->
    <?php include 'templates/admin/partials/sidebar.phtml'; ?>

    <!-- Conteúdo Principal do Painel -->
    <div class="flex-grow p-6 bg-gray-50">
        <?php
        switch ($section) {
            case 'users':
                if ($action === 'edit' && $id) {
                    $user_to_edit = User::findById($id);
                    include 'templates/admin/edit_user.phtml';
                } else {
                    $users = User::getAll();
                    include 'templates/admin/users.phtml';
                }
                break;
            
            case 'rooms':
                if ($action === 'edit' && $id) {
                    $room_to_edit = Room::findById($id);
                    $categories = RoomCategory::getAll();
                    include 'templates/admin/edit_room.phtml';
                } elseif ($action === 'create') {
                    $room_to_edit = null;
                    $categories = RoomCategory::getAll();
                    include 'templates/admin/edit_room.phtml';
                } else {
                    $rooms = Room::getGeneralRooms();
                    include 'templates/admin/rooms.phtml';
                }
                break;

            case 'plans':
                if ($action === 'edit' && $id) {
                    $plan_to_edit = Plan::findById($id);
                    include 'templates/admin/edit_plan.phtml';
                } elseif ($action === 'create') {
                    $plan_to_edit = null;
                    include 'templates/admin/edit_plan.phtml';
                } else {
                    $plans = Plan::getAll();
                    include 'templates/admin/plans.phtml';
                }
                break;

            case 'ads':
                if ($action === 'edit' && $id) {
                    $ad_to_edit = Ad::findById($id);
                    include 'templates/admin/edit_ad.phtml';
                } elseif ($action === 'create') {
                    $ad_to_edit = null;
                    include 'templates/admin/edit_ad.phtml';
                } else {
                    $ads = Ad::getAll();
                    include 'templates/admin/ads.phtml';
                }
                break;

            case 'contacts':
                if ($action === 'view' && $id) {
                    $contact_message = Contact::findById($id);
                    if ($contact_message && $contact_message['status'] === 'new') {
                        Contact::updateStatus($id, 'read');
                        $contact_message['status'] = 'read';
                    }
                    include 'templates/admin/view_contact.phtml';
                } else {
                    $contacts = Contact::getAll();
                    include 'templates/admin/contacts.phtml';
                }
                break;

            case 'avatars':
                $avatars = Avatar::getAll();
                include 'templates/admin/avatars.phtml';
                break;

            case 'subscriber-rooms':
                $subscriber_rooms = Room::getRoomsBySubscribers();
                include 'templates/admin/subscriber_rooms.phtml';
                break;
            
            case 'reports':
                if ($action === 'view' && $id) {
                    $report = Report::findById($id);
                    if ($report && $report['status'] === 'new') {
                        Report::updateStatus($id, 'reviewed');
                        $report['status'] = 'reviewed';
                    }
                    include 'templates/admin/view_report.phtml';
                } else {
                    $reports = Report::getAll();
                    include 'templates/admin/reports.phtml';
                }
                break;

            case 'blocked-words':
                $blocked_words = BlockedWord::getAll();
                include 'templates/admin/blocked_words.phtml';
                break;

            case 'infraction-logs':
                $logs = InfractionLog::getAll();
                include 'templates/admin/infraction_logs.phtml';
                break;

            case 'file-manager':
                $item_path_parts = array_slice($url_parts, 3);
                $item_path = implode('/', $item_path_parts);

                if ($action === 'edit') {
                    $file_content = FileManager::getFileContent($item_path);
                    if ($file_content !== false) {
                        include 'templates/admin/edit_file.phtml';
                    } else {
                        $errors[] = "Não foi possível ler o ficheiro ou o ficheiro não existe.";
                        $current_path = dirname($item_path);
                        $directory_content = FileManager::listDirectory($current_path);
                        include 'templates/admin/file_manager.phtml';
                    }
                } else if ($action === 'list') {
                    $current_path = $item_path;
                    $directory_content = FileManager::listDirectory($current_path);
                    include 'templates/admin/file_manager.phtml';
                } else {
                    $current_path = '';
                    $directory_content = FileManager::listDirectory($current_path);
                    include 'templates/admin/file_manager.phtml';
                }
                break;
            
            case 'manage-bots':
                $all_rooms = Room::getAll(); // Busca as salas para o formulário
                include 'templates/admin/manage_bots.phtml';
                break;
            
            case 'bot-messages':
                $bot_messages = BotMessage::getAll();
                include 'templates/admin/bot_messages.phtml';
                break;
            
            case 'categories':
                if ($action === 'edit' && $id) {
                    $category_to_edit = RoomCategory::findById($id);
                    include 'templates/admin/edit_category.phtml';
                } elseif ($action === 'create') {
                    $category_to_edit = null;
                    include 'templates/admin/edit_category.phtml';
                } else {
                    $categories = RoomCategory::getAll();
                    include 'templates/admin/categories.phtml';
                }
                break;

            case 'dashboard':
            default:
                $stats = [
                    'total_users' => User::countAll(),
                    'total_rooms' => Room::countAll(),
                    'online_users' => count(User::getOnlineUsers()),
                    'monthly_revenue' => Payment::getMonthlyRevenue()
                ];
                $registrationsData = User::getRegistrationsByDay();
                $chartLabels = [];
                $chartData = [];
                $registrationsMap = [];
                foreach ($registrationsData as $row) {
                    $registrationsMap[$row['registration_date']] = $row['count'];
                }
                for ($i = 29; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $chartLabels[] = date('d/m', strtotime($date));
                    $chartData[] = $registrationsMap[$date] ?? 0;
                }
                $stats['chart_labels'] = $chartLabels;
                $stats['chart_data'] = $chartData;
                include 'templates/admin/dashboard.phtml';
                break;
        }
        ?>
    </div>
</div>
