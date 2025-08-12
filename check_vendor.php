<?php
/**
 * Ferramenta de Diagnóstico do Composer
 * * Crie este ficheiro na raiz do seu projeto (public_html) e aceda a ele
 * através do navegador (ex: https://chat.webhop.me/check_vendor.php)
 * para verificar a sua instalação.
 */

echo "<h1>Verificação do Autoloader do Composer</h1>";

// Caminho para o ficheiro autoload.php
$autoload_path = __DIR__ . '/vendor/autoload.php';
echo "<p>A procurar por: <code>{$autoload_path}</code></p>";

// 1. Verificar se o ficheiro existe
if (file_exists($autoload_path)) {
    echo "<p style='color: green;'><strong>SUCESSO:</strong> O ficheiro <code>vendor/autoload.php</code> foi encontrado.</p>";
    
    // 2. Tentar incluir o ficheiro
    try {
        require_once $autoload_path;
        echo "<p style='color: green;'><strong>SUCESSO:</strong> O ficheiro <code>autoload.php</code> foi carregado sem erros.</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>ERRO:</strong> Ocorreu um erro ao carregar o <code>autoload.php</code>: " . $e->getMessage() . "</p>";
        exit;
    }

    // 3. Verificar se a classe do Stripe existe agora
    if (class_exists('Stripe\Stripe')) {
        echo "<p style='color: green;'><strong>SUCESSO:</strong> A classe <code>Stripe\Stripe</code> foi encontrada! A sua instalação do Composer está a funcionar corretamente.</p>";
        echo "<p>O problema pode estar no caminho relativo do ficheiro <code>stripe/create-checkout-session.php</code>. Verifique se o caminho <code>require_once '../vendor/autoload.php';</code> está correto para a sua estrutura de pastas.</p>";
    } else {
        echo "<p style='color: red;'><strong>ERRO:</strong> A classe <code>Stripe\Stripe</code> NÃO foi encontrada. Isto significa que a biblioteca do Stripe não está instalada corretamente na sua pasta <code>vendor</code>.</p>";
        echo "<p><strong>SOLUÇÃO:</strong> Execute o comando <code>composer install</code> na raiz do seu projeto para instalar todas as dependências.</p>";
    }

} else {
    echo "<p style='color: red;'><strong>ERRO CRÍTICO:</strong> O ficheiro <code>vendor/autoload.php</code> NÃO foi encontrado.</p>";
    echo "<p>Isto significa que as dependências do Composer não foram instaladas. Por favor, aceda ao seu servidor via SSH, navegue até à pasta <code>public_html</code> e execute o comando:</p>";
    echo "<pre><code>composer install</code></pre>";
}
?>
