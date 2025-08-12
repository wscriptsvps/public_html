<?php
/**
 * Ficheiro de Configuração Principal
 *
 * Guarde aqui todas as credenciais e configurações globais.
 * NUNCA partilhe este ficheiro publicamente.
 */

// Iniciar a sessão em todos os pedidos para manter o utilizador logado
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- CONFIGURAÇÃO DO BANCO DE DADOS ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'chat_webhop');
define('DB_USER', 'chat_webhop');
define('DB_PASS', 'chat_webhop');
define('DB_CHARSET', 'utf8mb4');

// --- CONFIGURAÇÃO DO SITE ---
define('SITE_URL', 'https://chat.webhop.me');
define('SITE_NAME', 'WebHop 2025');

// --- CONFIGURAÇÃO DA API STRIPE (para pagamentos) ---
// Chaves de Teste
define('STRIPE_SECRET_KEY_TEST', 'sk_test_51RhhYCLRzTwd11fyJ9kAV4XjsSS5puOdUAisribLu11DJpoHfcgbDbMBqPOSEKCBgJKE04kTmMyxIq1eDd5d8jWG009NbE7Jxc');
define('STRIPE_PUBLIC_KEY_TEST', 'pk_test_51RhhYCLRzTwd11fyMugOpZt56snW9mPSHkTFIE7CoUUIMjZ80sYnGF22oVsUvNGmhkavHWhkL4TMfboUbWx20k2g00MEJJVHr4');
define('STRIPE_WEBHOOK_SECRET_TEST', 'whsec_2FxhVaPjqSe0SRbq2PW0QOv7ssDqJEhq');

// Chaves de Produção (Live)
define('STRIPE_SECRET_KEY_LIVE', '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');
define('STRIPE_PUBLIC_KEY_LIVE', 'pk_live_51RhhYCLRzTwd11fyY1oY5shwrYCsF7MLj71vEb0PBFpq4XldmL4Ke2xIVXFxEw9lgW6VSKol7CWhs3luoExuYxLf00mCFyxf9E');
define('STRIPE_WEBHOOK_SECRET_LIVE', 'whsec_8RlrTyFboiAIL6Q8qdZjyfVQKEuXYekX');

// --- CONFIGURAÕES DE SEGURANÇA ---
define('RESERVED_NAMES', [
    'admin', 'administrador', 'adm',
    'mod', 'moderador',
    'root', 'sistema', 'suporte',
    'bpbol', 'uol', 'ulme', 'google', 'facebook', 'bol', 'instagram' // Nomes relacionados à marca
]);

// --- CONFIGURAÇÃO DE E-MAIL (SMTP) ---
define('SMTP_HOST', 'chat.webhop.me');
define('SMTP_USER', 'chat@chat.webhop.me');
define('SMTP_PASS', 'BkA23o!qq@7BxLLf');
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'no-reply@bpbol.com.br');
define('SMTP_FROM_NAME', SITE_NAME);

// --- CONFIGURAÇÕES DE ERRO ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- FUSO HORRIO ---
date_default_timezone_set('America/Sao_Paulo');
?>
