<?php
$success_message = '';
$errors = [];
$name = '';
$email = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $errors[] = "Todos os campos são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "O endereço de e-mail não é válido.";
    } else {
        if (Contact::create($name, $email, $subject, $message)) {
            $success_message = "A sua mensagem foi enviada com sucesso! Entraremos em contato em breve.";
            // Limpa os campos após o envio
            $name = $email = $subject = $message = '';
        } else {
            $errors[] = "Ocorreu um erro ao enviar a sua mensagem. Por favor, tente novamente.";
        }
    }
}
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold text-center text-purple-800 mb-4">Entre em Contato</h1>
    <p class="text-center text-gray-600 mb-8">Tem alguma dúvida, sugestão ou problema? Preencha o formulário abaixo.</p>

    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
        </div>
    <?php endif; ?>

    <div class="bg-white p-8 rounded-lg shadow-md">
        <form action="/contact" method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="name" class="block text-gray-700 font-bold mb-2">Seu Nome</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                <div>
                    <label for="email" class="block text-gray-700 font-bold mb-2">Seu E-mail</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
            </div>
            <div class="mb-6">
                <label for="subject" class="block text-gray-700 font-bold mb-2">Assunto</label>
                <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject); ?>" class="w-full px-3 py-2 border rounded-lg" required>
            </div>
            <div class="mb-6">
                <label for="message" class="block text-gray-700 font-bold mb-2">Mensagem</label>
                <textarea id="message" name="message" rows="6" class="w-full px-3 py-2 border rounded-lg" required><?php echo htmlspecialchars($message); ?></textarea>
            </div>
            <button type="submit" class="w-full bg-orange-500 text-white font-bold py-3 px-6 rounded-lg hover:bg-orange-600 transition duration-300">
                Enviar Mensagem
            </button>
        </form>
    </div>
</div>
