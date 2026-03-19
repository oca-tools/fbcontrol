<?php
function normalize_mojibake(string $value): string
{
    static $map = [
        'Ã¡' => 'á',
        'Ãà' => 'à',
        'Ã¢' => 'â',
        'Ã£' => 'ã',
        'Ã¤' => 'ä',
        'Ã©' => 'é',
        'Ã¨' => 'è',
        'Ãª' => 'ê',
        'Ã«' => 'ë',
        'Ã­' => 'í',
        'Ã¬' => 'ì',
        'Ã®' => 'î',
        'Ã¯' => 'ï',
        'Ã³' => 'ó',
        'Ã²' => 'ò',
        'Ã´' => 'ô',
        'Ãµ' => 'õ',
        'Ã¶' => 'ö',
        'Ãº' => 'ú',
        'Ã¹' => 'ù',
        'Ã»' => 'û',
        'Ã¼' => 'ü',
        'Ã§' => 'ç',
        'Ã' => 'Ç',
        'Ã' => 'Á',
        'Ã' => 'À',
        'Ã' => 'Â',
        'Ã' => 'Ã',
        'Ã' => 'É',
        'Ã' => 'Ê',
        'Ã' => 'Í',
        'Ã' => 'Ó',
        'Ã' => 'Ô',
        'Ã' => 'Õ',
        'Ã' => 'Ú',
        'Ãœ' => 'Ü',
        'Ã±' => 'ñ',
        'Âº' => 'º',
        'Âª' => 'ª',
        'Â°' => '°',
        'Â' => '',
        'NÃ£o' => 'Não',
        'OperaÃ§Ã£o' => 'Operação',
        'OperaÃ§Ãµes' => 'Operações',
        'AÃ§Ã£o' => 'Ação',
        'AÃ§Ãµes' => 'Ações',
        'ConferÃªncia' => 'Conferência',
        'DistribuiÃ§Ã£o' => 'Distribuição',
        'ObservaÃ§Ã£o' => 'Observação',
        'ObservaÃ§Ãµes' => 'Observações',
        'Ãšltimos' => 'Últimos',
        'HorÃ¡rio' => 'Horário',
        'HorÃ¡rios' => 'Horários',
        'invÃ¡lido' => 'inválido',
        'invÃ¡lida' => 'inválida',
        'invÃ¡lidos' => 'inválidos',
        'invÃ¡lidas' => 'inválidas',
        'nÃ£o' => 'não',
        'temÃ¡tico' => 'temático',
        'TemÃ¡tico' => 'Temático',
        'almoÃ§o' => 'almoço',
        'AlmoÃ§o' => 'Almoço',
        'cafÃ©' => 'café',
        'CafÃ©' => 'Café',
        'ÃƒÂ£' => 'ã',
        'ÃƒÂ¡' => 'á',
        'ÃƒÂ©' => 'é',
        'ÃƒÂ§' => 'ç',
        'ÃƒÂ³' => 'ó',
    ];

    return strtr($value, $map);
}

function normalize_output_mojibake(string $buffer): string
{
    return normalize_mojibake($buffer);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validate(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => normalize_mojibake($message)];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function h(string $value): string
{
    return htmlspecialchars(normalize_mojibake($value), ENT_QUOTES, 'UTF-8');
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function restaurant_badge_class(string $name): string
{
    $n = mb_strtolower($name, 'UTF-8');
    if (strpos($n, 'corais') !== false) {
        return 'tag-rest-corais';
    }
    if (strpos($n, 'giardino') !== false) {
        return 'tag-rest-giardino';
    }
    if (strpos($n, 'brasa') !== false) {
        return 'tag-rest-brasa';
    }
    if (strpos($n, 'ix') !== false || strpos($n, 'ixu') !== false) {
        return 'tag-rest-ixu';
    }
    if (strpos($n, 'privileged') !== false) {
        return 'tag-rest-privileged';
    }
    return 'tag-rest-default';
}

function operation_badge_class(string $name): string
{
    $n = mb_strtolower($name, 'UTF-8');
    if (strpos($n, 'café') !== false || strpos($n, 'cafe') !== false) {
        return 'tag-op-cafe';
    }
    if (strpos($n, 'almoço') !== false || strpos($n, 'almoco') !== false) {
        return 'tag-op-almoco';
    }
    if (strpos($n, 'jantar') !== false) {
        return 'tag-op-jantar';
    }
    if (strpos($n, 'temático') !== false || strpos($n, 'tematico') !== false) {
        return 'tag-op-tematico';
    }
    if (strpos($n, 'privileged') !== false) {
        return 'tag-op-privileged';
    }
    return 'tag-op-default';
}

function uh_badge_class($uh): string
{
    $num = (int)$uh;
    if (($num >= 100 && $num <= 299)) {
        return 'uh-bungalow';
    }
    if ($num >= 300 && $num <= 1019) {
        return 'uh-standard';
    }
    if ($num >= 1101 && $num <= 1112) {
        return 'uh-family';
    }
    if ($num >= 2100 && $num <= 4322) {
        return 'uh-nova';
    }
    return 'uh-default';
}
