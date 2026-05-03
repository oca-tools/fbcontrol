<?php
/** polyfill PHP 7 for str_starts_with/contains/ends_with */
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        if ($needle === '') return true;
        $len = strlen($needle);
        return substr($haystack, -$len) === $needle;
    }
}

function normalize_mojibake(string $value): string
{
    $normalized = str_replace("\xEF\xBB\xBF", '', $value);

    $score = static function (string $text): int {
        return (int)preg_match_all('/(?:\x{00C3}[\x{0080}-\x{00BF}]|\x{00C2}[\x{0080}-\x{00BF}]|\x{00E2}\x{20AC}|\x{FFFD})/u', $text);
    };

    for ($i = 0; $i < 4; $i++) {
        $baseScore = $score($normalized);
        if ($baseScore === 0) {
            break;
        }

        $candidates = [
            @utf8_decode($normalized),
            @mb_convert_encoding($normalized, 'ISO-8859-1', 'UTF-8'),
            @mb_convert_encoding($normalized, 'Windows-1252', 'UTF-8'),
        ];

        $best = $normalized;
        $bestScore = $baseScore;

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || $candidate === '' || !mb_check_encoding($candidate, 'UTF-8')) {
                continue;
            }
            $candidateScore = $score($candidate);
            if ($candidateScore < $bestScore) {
                $best = $candidate;
                $bestScore = $candidateScore;
            }
        }

        if ($best === $normalized) {
            break;
        }
        $normalized = $best;
    }

    // Remove common leftover artifacts.
    $normalized = str_replace("\xC2\xA0", ' ', $normalized);
    $normalized = preg_replace('/\x{00C2}(?=\s|[\d%\)\]\.,;:!?])/u', '', $normalized) ?? $normalized;

    return $normalized;
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

function ini_size_to_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    if ($value === '-1') {
        return PHP_INT_MAX;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float)$value;
    switch ($unit) {
        case 'g':
            $number *= 1024;
            // no break
        case 'm':
            $number *= 1024;
            // no break
        case 'k':
            $number *= 1024;
            break;
    }

    return (int)ceil($number);
}

function upload_limit_bytes(int $appMaxBytes): int
{
    $limits = [$appMaxBytes];
    foreach (['upload_max_filesize', 'post_max_size'] as $key) {
        $bytes = ini_size_to_bytes((string)ini_get($key));
        if ($bytes > 0 && $bytes < PHP_INT_MAX) {
            $limits[] = $bytes;
        }
    }
    return min($limits);
}

function format_bytes_ptbr(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        $mb = $bytes / (1024 * 1024);
        $decimals = abs($mb - round($mb)) < 0.01 ? 0 : 1;
        return number_format($mb, $decimals, ',', '.') . 'MB';
    }
    if ($bytes >= 1024) {
        $kb = $bytes / 1024;
        $decimals = abs($kb - round($kb)) < 0.01 ? 0 : 1;
        return number_format($kb, $decimals, ',', '.') . 'KB';
    }
    return $bytes . 'B';
}

function sanitize_date_param(?string $value, string $default = ''): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return $default;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $default;
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return $default;
    }
    return date('Y-m-d', $ts);
}

function sanitize_int_param($value, int $min = 1): string
{
    if ($value === null || $value === '') {
        return '';
    }
    if (!is_numeric($value)) {
        return '';
    }
    $intValue = (int)$value;
    if ($intValue < $min) {
        return '';
    }
    return (string)$intValue;
}

function sanitize_enum_param(?string $value, array $allowed, string $default = ''): string
{
    $value = trim(mb_strtolower((string)$value, 'UTF-8'));
    if ($value === '') {
        return $default;
    }
    return in_array($value, $allowed, true) ? $value : $default;
}

function sanitize_uh_param(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    if (!preg_match('/^\d{1,6}$/', $value)) {
        return '';
    }
    return $value;
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
    $n = mb_strtolower(normalize_mojibake($name), 'UTF-8');
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
    $n = mb_strtolower(normalize_mojibake($name), 'UTF-8');
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
