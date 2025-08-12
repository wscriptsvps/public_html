<?php
$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $gender = $_POST['gender'] ?? 'none';

    // Validações
    if (empty($name)) $errors[] = "O campo Nome é obrigatório.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Formato de e-mail inválido.";
    
    // --- VERIFICAÇÃO DE E-MAIL DUPLICADO (CORRIGIDA) ---
    if (User::findByEmail($email)) {
        $errors[] = "Este e-mail já está registado. Por favor, utilize outro ou faça login.";
    }

    if (strlen($password) < 6) $errors[] = "A senha deve ter no mínimo 6 caracteres.";
    if ($password !== $password_confirm) $errors[] = "As senhas não coincidem.";
    if (!in_array($gender, ['male', 'female', 'none'])) $gender = 'none';

    // Validação de Nomes Reservados
    foreach (RESERVED_NAMES as $reserved) {
        if (stripos($name, $reserved) !== false) {
            $errors[] = "O nome '".htmlspecialchars($name)."' não é permitido. Por favor, escolha outro.";
            break;
        }
    }

    // Apenas tenta criar o utilizador se não houver erros
    if (empty($errors)) {
        $newUser = User::create($name, $email, $password, $gender);
        if ($newUser) {
            Auth::login($newUser);
            header('Location: /?registered=true');
            exit();
        } else {
            $errors[] = "Ocorreu um erro ao criar a sua conta.";
        }
    }
}
?>

<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-center text-purple-800 mb-6">Criar Nova Conta</h1>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
        </div>
    <?php endif; ?>

    <form action="/register" method="POST">
        <div class="mb-4">
            <label for="name" class="block text-gray-700 font-bold mb-2">Nome</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-bold mb-2">E-mail</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Gênero</label>
            <div class="flex justify-around">
                <label class="flex items-center"><input type="radio" name="gender" value="male" class="form-radio h-5 w-5 text-purple-600"> <span class="ml-2">Masculino</span></label>
                <label class="flex items-center"><input type="radio" name="gender" value="female" class="form-radio h-5 w-5 text-purple-600"> <span class="ml-2">Feminino</span></label>
                <label class="flex items-center"><input type="radio" name="gender" value="none" checked class="form-radio h-5 w-5 text-purple-600"> <span class="ml-2">Não informar</span></label>
            </div>
        </div>
        <div class="mb-4">
            <label for="password" class="block text-gray-700 font-bold mb-2">Senha</label>
            <input type="password" id="password" name="password" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="mb-6">
            <label for="password_confirm" class="block text-gray-700 font-bold mb-2">Confirmar Senha</label>
            <input type="password" id="password_confirm" name="password_confirm" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <button type="submit" class="w-full bg-orange-500 text-white font-bold py-3 px-6 rounded-lg hover:bg-orange-600">Registar</button>
    </form>
</div>
