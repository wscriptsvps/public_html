<?php
// A lógica de proteção (verificar se já existe admin) está no index.php

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validações
    if (empty($name)) $errors[] = "O campo Nome é obrigatório.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Formato de e-mail inválido.";
    if (strlen($password) < 6) $errors[] = "A senha deve ter no mínimo 6 caracteres.";
    if ($password !== $password_confirm) $errors[] = "As senhas não coincidem.";

    if (empty($errors)) {
        // --- LINHA CORRIGIDA ---
        // Agora, a função é chamada com os argumentos na ordem correta:
        // create($name, $email, $password, $gender, $account_type)
        $newAdmin = User::create($name, $email, $password, 'none', 'admin');
        
        if ($newAdmin) {
            // Login automático
            Auth::login($newAdmin);
            // Redireciona para o painel de administração
            header('Location: /admin');
            exit();
        } else {
            $errors[] = "Ocorreu um erro ao criar a conta de administrador. Tente novamente.";
        }
    }
}
?>

<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md mt-10">
    <h1 class="text-2xl font-bold text-center text-purple-800 mb-2">Criar Conta de Administrador</h1>
    <p class="text-center text-gray-600 mb-6">Esta é a primeira configuração do site. Esta conta terá acesso total ao painel de administração.</p>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="/create-admin" method="POST">
        <div class="mb-4">
            <label for="name" class="block text-gray-700 font-bold mb-2">Nome do Administrador</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
        </div>
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-bold mb-2">E-mail</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
        </div>
        <div class="mb-4">
            <label for="password" class="block text-gray-700 font-bold mb-2">Senha</label>
            <input type="password" id="password" name="password" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
        </div>
        <div class="mb-6">
            <label for="password_confirm" class="block text-gray-700 font-bold mb-2">Confirmar Senha</label>
            <input type="password" id="password_confirm" name="password_confirm" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
        </div>
        <button type="submit" class="w-full bg-orange-500 text-white font-bold py-3 px-6 rounded-lg hover:bg-orange-600 transition duration-300">
            Criar Administrador e Entrar
        </button>
    </form>
</div>
