<?php

function normalize_mojibake(string $value): string
{
    $normalized = str_replace("\xEF\xBB\xBF", '', $value);

    $score = static function (string $text): int {
        return (int)preg_match_all('/Ã|Â|â|ï¿½|\?fï¿½/u', $text);
    };

    // Tenta reparar UTF-8 lido como ISO-8859-1/Windows-1252
    for ($i = 0; $i < 2; $i++) {
        $baseScore = $score($normalized);
        $iso = @mb_convert_encoding($normalized, 'UTF-8', 'ISO-8859-1');
        $win = @mb_convert_encoding($normalized, 'UTF-8', 'Windows-1252');

        $best = $normalized;
        $bestScore = $baseScore;
        foreach ([$iso, $win] as $candidate) {
            if (is_string($candidate) && $candidate !== '' && $score($candidate) < $bestScore) {
                $best = $candidate;
                $bestScore = $score($candidate);
            }
        }

        if ($best === $normalized) {
            break;
        }
        $normalized = $best;
    }

    static $phraseMap = [
        'Opera?fï¿½?fï¿½o' => 'Operação',
        'opera?fï¿½?fï¿½o' => 'operação',
        'Operaï¿½ï¿½o' => 'Operação',
        'operaï¿½ï¿½o' => 'operação',
        'OperaÃ§Ã£o' => 'Operação',
        'operaÃ§Ã£o' => 'operação',
        'N?fï¿½o' => 'Não',
        'n?fï¿½o' => 'não',
        'Nï¿½o' => 'Não',
        'nï¿½o' => 'não',
        'Usu?fï¿½rio' => 'Usuário',
        'Usuï¿½rio' => 'Usuário',
        'N?fï¿½mero' => 'Número',
        'Hor?fï¿½rio' => 'Horário',
        'hor?fï¿½rio' => 'horário',
        'In?fï¿½cio' => 'Início',
        'in?fï¿½cio' => 'início',
        'Tem?fï¿½tico' => 'Temático',
        'tem?fï¿½tico' => 'temático',
        'Relat?fï¿½rios' => 'Relatórios',
        'Consolida?fï¿½?fï¿½o' => 'Consolidação',
        'Movimenta?fï¿½?fï¿½o' => 'Movimentação',
        'obrigat?fï¿½ria' => 'obrigatória',
        'confirma?fï¿½?fï¿½o' => 'confirmação',
        'corre?fï¿½?fï¿½o' => 'correção',
        'di?fï¿½rio' => 'diário',
        'refei?fï¿½?fï¿½es' => 'refeições',
        'refei?fï¿½?fï¿½o' => 'refeição',
        'Mï¿½ltiplo' => 'Múltiplo',
        'Mï¿½ltiplos' => 'Múltiplos',
        '?fï¿½ltimo' => 'último',
        'ï¿½ltimos' => 'Últimos',
        'r?fï¿½pida' => 'rápida',
        'r?fï¿½pido' => 'rápido',
        'Ã¢â‚¬Â¢' => '•',
    ];

    return strtr($normalized, $phraseMap);
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
    if ($num === 998) {
        return 'uh-nao-informado';
    }
    if ($num === 999) {
        return 'uh-day-use';
    }
    if ($num >= 100 && $num <= 299) {
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

function uh_label($uh): string
{
    $num = (string)$uh;
    if ($num === '998') {
        return '998 (Não informado)';
    }
    if ($num === '999') {
        return '999 (Day use)';
    }
    return $num;
}
