<?php
$success_message = '';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $user = User::findByEmail($email);
    if ($user) {
        $code = User::generatePasswordResetToken($email);
        if ($code && Mail::sendPasswordReset($user['email'], $user['name'], $code)) {
            // Redireciona para a página de redefinição, passando o e-mail como parâmetro
            header('Location: /reset-password?email=' . urlencode($email));
            exit();
        } else {
            $error_message = "Não foi possível enviar o e-mail. Por favor, tente novamente mais tarde.";
        }
    } else {
        // Mensagem genérica para não confirmar se um e-mail existe ou não
        $error_message = "Se a sua conta existir, um e-mail de redefinição será enviado. Verifique a sua caixa de spam.";
    }
}
?>
<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-center text-purple-800 mb-6">Esqueceu sua Senha?</h1>
    <p class="text-center text-gray-600 mb-6">Insira o seu e-mail abaixo e enviaremos um código de 6 dígitos para redefinir a sua senha.</p>
    <?php if ($error_message): ?><div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error_message; ?></div><?php endif; ?>
    <form action="/forgot-password" method="POST">
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-bold mb-2">E-mail</label>
            <input type="email" id="email" name="email" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <button type="submit" class="w-full bg-orange-500 text-white font-bold py-3 rounded-lg hover:bg-orange-600">Enviar Código</button>
    </form>
</div>
