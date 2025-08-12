<?php
$email = $_GET['email'] ?? '';
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $code = trim($_POST['code']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    $user = User::findByEmailAndResetCode($email, $code);

    if ($user) {
        if (strlen($password) < 6) {
            $error_message = "A senha deve ter no mínimo 6 caracteres.";
        } elseif ($password !== $password_confirm) {
            $error_message = "As senhas não coincidem.";
        } else {
            if (User::resetPassword($user['id'], $password)) {
                $success_message = "A sua senha foi redefinida com sucesso! Agora pode fazer login com a nova senha.";
            } else {
                $error_message = "Ocorreu um erro ao redefinir a sua senha.";
            }
        }
    } else {
        $error_message = "Código inválido, expirado ou o e-mail está incorreto. Por favor, tente novamente.";
    }
}
?>
<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-center text-purple-800 mb-6">Redefinir Senha</h1>
    <?php if ($success_message): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo $success_message; ?></div>
        <a href="/login" class="block w-full text-center bg-purple-600 text-white font-bold py-3 rounded-lg hover:bg-purple-700">Ir para Login</a>
    <?php else: ?>
        <p class="text-center text-gray-600 mb-6">Por favor, insira o seu e-mail, o código de 6 dígitos que recebeu e a sua nova senha.</p>
        <?php if ($error_message): ?><div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error_message; ?></div><?php endif; ?>
        <form action="/reset-password" method="POST">
            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-bold mb-2">E-mail</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label for="code" class="block text-gray-700 font-bold mb-2">Código de 6 Dígitos</label>
                <input type="text" id="code" name="code" required class="w-full px-3 py-2 border rounded-lg" maxlength="6">
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-bold mb-2">Nova Senha</label>
                <input type="password" id="password" name="password" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-6">
                <label for="password_confirm" class="block text-gray-700 font-bold mb-2">Confirmar Nova Senha</label>
                <input type="password" id="password_confirm" name="password_confirm" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <button type="submit" class="w-full bg-orange-500 text-white font-bold py-3 rounded-lg hover:bg-orange-600">Redefinir Senha</button>
        </form>
    <?php endif; ?>
</div>
