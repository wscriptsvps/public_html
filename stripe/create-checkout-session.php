<?php
require_once '../vendor/autoload.php';
require_once '../src/config.php';
require_once '../src/Core/Database.php';
require_once '../src/Core/Auth.php';
require_once '../src/Models/Plan.php';
require_once '../src/Models/Setting.php';

if (!Auth::isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login');
    exit();
}

$plan_id = $_POST['plan_id'];
$user_id = Auth::getUserId();
$plan = Plan::findById($plan_id);
if (!$plan) die("Plano não encontrado.");

// Lógica para usar as chaves corretas
$stripe_mode = Setting::get('stripe_mode');
$api_key = ($stripe_mode === 'live') ? STRIPE_SECRET_KEY_LIVE : STRIPE_SECRET_KEY_TEST;

\Stripe\Stripe::setApiKey($api_key);

try {
    // --- LÓGICA DE PRODUTO E PREÇO TOTALMENTE DINÂMICA ---

    // 1. Cria um Produto no Stripe usando o nome do plano do seu site
    $product = \Stripe\Product::create([
        'name' => $plan['name'],
    ]);

    // 2. Cria um Preço dinamicamente para este novo Produto
    $price = \Stripe\Price::create([
        'product' => $product->id, // Usa o ID do produto recém-criado
        'unit_amount' => $plan['price'] * 100, // Converte para centavos
        'currency' => 'brl',
        'recurring' => ['interval' => 'month', 'interval_count' => $plan['interval_months']],
    ]);

    // 3. Cria a sessão de checkout com o preço recém-criado
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card', 'boleto'],
        'line_items' => [['price' => $price->id, 'quantity' => 1]],
        'mode' => 'subscription',
        'success_url' => SITE_URL . '/payment-success',
        'cancel_url' => SITE_URL . '/plans',
        'metadata' => ['user_id' => $user_id, 'plan_id' => $plan_id]
    ]);

    header("HTTP/1.1 303 See Other");
    header("Location: " . $checkout_session->url);
} catch (Exception $e) {
    http_response_code(500);
    // Para depuração, pode ser útil ver o erro exato
    // error_log($e->getMessage());
    echo json_encode(['error' => 'Ocorreu um erro ao comunicar com o sistema de pagamento.']);
}
