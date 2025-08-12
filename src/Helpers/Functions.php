<?php
function create_slug($string) {
    $string = preg_replace('/[^\p{L}\p{N}]+/u', '-', $string);
    $string = trim($string, '-');
    $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]+/', '', $string);
    return $string;
}

/**
 * Verifica se um tipo de conta de utilizador tem acesso a um nível requerido.
 * @param string $user_type - O tipo de conta do utilizador (ex: 'common', 'vip').
 * @param string $required_level - O nível de acesso requerido pela sala.
 * @return bool - True se o utilizador tiver acesso, false caso contrário.
 */
function userHasAccess($user_type, $required_level) {
    // Define uma hierarquia de poder para cada tipo de conta.
    $levels = [
        'common' => 1,
        'vip' => 2,
        'moderator' => 3,
        'admin' => 4
    ];

    // Obtém o nível numérico do utilizador e o nível requerido.
    $user_level = $levels[$user_type] ?? 0; // Se não estiver logado, o nível é 0.
    $required = $levels[$required_level] ?? 1; // Por padrão, o acesso é 'common'.

    // O utilizador tem acesso se o seu nível for igual ou superior ao requerido.
    return $user_level >= $required;
}
