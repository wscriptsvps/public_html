<?php
/**
 * Classe Filter
 *
 * Contém a lógica para validar e filtrar o conteúdo das mensagens.
 */
class Filter {

    private static $blocked_words = null;

    /**
     * Carrega a lista de palavras bloqueadas do banco de dados.
     */
    private static function loadBlockedWords() {
        if (self::$blocked_words === null) {
            $words_data = BlockedWord::getAll();
            self::$blocked_words = array_map(function($item) {
                return $item['word'];
            }, $words_data);
        }
    }

    /**
     * Valida uma mensagem contra todas as regras de filtro.
     * @param string $message
     * @param int $user_id
     * @return string|true - Retorna uma string com o motivo do bloqueio ou true se for válida.
     */
    public static function validate($message, $user_id) {
        self::loadBlockedWords();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // 1. Filtro de Palavras Proibidas
        foreach (self::$blocked_words as $word) {
            if (stripos($message, $word) !== false) {
                InfractionLog::create($user_id, $ip_address, $message, "Palavra proibida detectada: '{$word}'");
                return "A sua mensagem contém palavras não permitidas.";
            }
        }

        // 2. Filtro de Links ATUALIZADO
        // Apenas se aplica a utilizadores que não são administradores ou moderadores
        if ($_SESSION['user_account_type'] === 'common' || $_SESSION['user_account_type'] === 'vip') {
            // Verifica se a mensagem contém um link
            if (preg_match('/https?:\/\/[^\s]+/i', $message, $matches)) {
                $link = $matches[0]; // Captura o primeiro link encontrado

                // Define os padrões de links que são permitidos
                $allowed_patterns = [
                    '/\.(jpg|jpeg|png|gif|webp)$/i', // Imagens
                    '/\.mp3$/i',                     // Áudio MP3
                    '/(youtube\.com|youtu\.be)/i'    // Vídeos do YouTube
                ];

                $is_allowed = false;
                foreach ($allowed_patterns as $pattern) {
                    if (preg_match($pattern, $link)) {
                        $is_allowed = true;
                        break; // Se encontrar um padrão permitido, para a verificação
                    }
                }

                // Se o link não corresponder a nenhum padrão permitido, bloqueia a mensagem
                if (!$is_allowed) {
                    InfractionLog::create($user_id, $ip_address, $message, "Tentativa de envio de link não permitido.");
                    return "O envio deste tipo de link não é permitido. Apenas imagens, MP3 e vídeos do YouTube são autorizados.";
                }
            }
        }

        return true; // Mensagem é válida
    }
}
