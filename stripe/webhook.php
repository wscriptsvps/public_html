<?php
require_once '../vendor/autoload.php';
require_once '../src/config.php';
require_once '../src/Core/Database.php';
require_once '../src/Models/User.php';
require_once '../src/Models/Plan.php';
require_once '../src/Models/Payment.php';
require_once '../src/Models/Setting.php';

// --- LOGGING ---
$log_file = dirname(__DIR__) . '/logs/webhook.log';
function write_log($message) {
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
}
// --- FIM DO LOGGING ---

write_log("Webhook atingido.");

// Lógica para usar as chaves corretas
$stripe_mode = Setting::get('stripe_mode');
$webhook_secret = ($stripe_mode === 'live') ? STRIPE_WEBHOOK_SECRET_LIVE : STRIPE_WEBHOOK_SECRET_TEST;

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $webhook_secret);
    write_log("Assinatura do webhook verificada com sucesso. Evento: " . $event->type);
} catch(\UnexpectedValueException $e) {
    write_log("ERRO: Payload inválido. " . $e->getMessage());
    http_response_code(400); exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    write_log("ERRO: Falha na verificação da assinatura. A sua 'Signing secret' está correta? " . $e->getMessage());
    http_response_code(400); exit();
}

// Processa o evento
if ($event->type == 'checkout.session.completed') {
    $session = $event->data->object;
    $metadata = $session->metadata;
    
    write_log("Processando evento checkout.session.completed.");
    write_log("Metadata recebida: " . json_encode($metadata));
    
    $user_id = $metadata->user_id ?? null;
    $plan_id = $metadata->plan_id ?? null;
    $stripe_charge_id = $session->payment_intent ?? $session->subscription;
    $amount = $session->amount_total;

    if (!$user_id || !$plan_id) {
        write_log("ERRO CRÍTICO: user_id ou plan_id em falta nos metadata. A atualização não pode continuar.");
        http_response_code(400);
        exit();
    }

    $plan = Plan::findById($plan_id);
    if ($plan) {
        write_log("Plano #{$plan_id} encontrado. A atualizar utilizador #{$user_id} para VIP.");
        // Atualiza a conta do utilizador para VIP
        User::upgradeToVip($user_id, $plan_id, $plan['interval_months']);
        // Regista o pagamento no histórico
        Payment::create($user_id, $plan_id, $amount, $stripe_charge_id);
        write_log("Utilizador #{$user_id} atualizado e pagamento registado com sucesso.");
    } else {
        write_log("ERRO: Plano #{$plan_id} não encontrado no banco de dados.");
    }
}

http_response_code(200);
