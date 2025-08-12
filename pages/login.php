<?php
$errors = [];
$email = '';

// Processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validações
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Formato de e-mail inválido.";
    } else {
        $user = User::findByEmail($email);
        
        // Verifica se o utilizador existe e se a senha está correta
        if ($user && password_verify($password, $user['password'])) {
            Auth::login($user);
            header('Location: /'); // Redireciona para a página principal
            exit();
        } else {
            $errors[] = "E-mail ou senha incorretos.";
        }
    }
}
?>

<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-center text-purple-800 mb-6">Entrar na sua Conta</h1>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <span><?php echo $errors[0]; ?></span>
        </div>
    <?php endif; ?>

    <form action="/login" method="POST">
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-bold mb-2">E-mail</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
        </div>
        <div class="mb-4">
            <label for="password" class="block text-gray-700 font-bold mb-2">Senha</label>
            <input type="password" id="password" name="password" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
        </div>
        <div class="flex justify-end mb-6">
            <a href="/forgot-password" class="text-sm text-purple-600 hover:underline">Esqueceu sua senha?</a>
        </div>
        <button type="submit" class="w-full bg-orange-500 text-white font-bold py-3 px-6 rounded-lg hover:bg-orange-600 transition duration-300">
            Entrar
        </button>
    </form>
    <p class="text-center mt-4">
        Não tem uma conta? <a href="/register" class="text-purple-600 hover:underline">Crie uma agora</a>.
    </p>
</div>
