<?php
declare(strict_types=1);

$root = realpath(__DIR__ . '/../../');
if ($root === false) {
    fwrite(STDERR, "Nao foi possivel localizar raiz do projeto.\n");
    exit(2);
}

$targets = ['app', 'public', 'config'];
$rules = [
    [
        'id' => 'RCE_EVAL',
        'severity' => 'CRITICAL',
        'pattern' => '/\beval\s*\(/i',
        'message' => 'Uso de eval() pode permitir execucao remota de codigo.',
    ],
    [
        'id' => 'RCE_EXEC',
        'severity' => 'HIGH',
        'pattern' => '/(?<!->)\b(shell_exec|exec|system|passthru|proc_open|popen)\s*\(/i',
        'message' => 'Funcao de execucao de comando encontrada.',
    ],
    [
        'id' => 'UNSERIALIZE',
        'severity' => 'HIGH',
        'pattern' => '/\bunserialize\s*\(/i',
        'message' => 'Uso de unserialize() exige origem altamente confiavel.',
    ],
    [
        'id' => 'OPEN_REDIRECT',
        'severity' => 'MEDIUM',
        'pattern' => '/header\s*\(\s*[\'"]Location:\s*\.\s*\$/i',
        'message' => 'Possivel redirect com concatenacao dinamica.',
    ],
    [
        'id' => 'SQL_INTERPOLATION',
        'severity' => 'MEDIUM',
        'pattern' => '/->query\s*\(\s*"[^"]*\$[A-Za-z_][A-Za-z0-9_]*[^"]*"\s*\)/',
        'message' => 'Query SQL com interpolacao direta de variavel.',
    ],
];

$findings = [];
$totals = ['CRITICAL' => 0, 'HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0];

$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iter as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile()) {
        continue;
    }

    $path = str_replace('\\', '/', $file->getPathname());
    $relative = ltrim(str_replace(str_replace('\\', '/', $root), '', $path), '/');

    $inTarget = false;
    foreach ($targets as $target) {
        if (str_starts_with($relative, $target . '/')) {
            $inTarget = true;
            break;
        }
    }
    if (!$inTarget) {
        continue;
    }

    if (!preg_match('/\.(php|phtml|inc)$/i', $relative)) {
        continue;
    }

    $lines = @file($file->getPathname());
    if (!is_array($lines)) {
        continue;
    }

    foreach ($lines as $lineNum => $line) {
        $trim = ltrim($line);
        if (str_starts_with($trim, '//') || str_starts_with($trim, '#')) {
            continue;
        }
        foreach ($rules as $rule) {
            if (!preg_match($rule['pattern'], $line)) {
                continue;
            }

            $excerpt = trim(preg_replace('/\s+/', ' ', $line) ?? '');
            $findings[] = [
                'severity' => $rule['severity'],
                'rule' => $rule['id'],
                'file' => $relative,
                'line' => $lineNum + 1,
                'message' => $rule['message'],
                'excerpt' => mb_substr($excerpt, 0, 180),
            ];
            $totals[$rule['severity']]++;
        }
    }
}

usort($findings, static function (array $a, array $b): int {
    $order = ['CRITICAL' => 4, 'HIGH' => 3, 'MEDIUM' => 2, 'LOW' => 1];
    $oa = $order[$a['severity']] ?? 0;
    $ob = $order[$b['severity']] ?? 0;
    if ($oa !== $ob) {
        return $ob <=> $oa;
    }
    if ($a['file'] !== $b['file']) {
        return $a['file'] <=> $b['file'];
    }
    return $a['line'] <=> $b['line'];
});

echo "SAST scan (baseline) - " . date('Y-m-d H:i:s') . PHP_EOL;
echo "Root: {$root}" . PHP_EOL . PHP_EOL;
echo "Resumo:" . PHP_EOL;
foreach (['CRITICAL', 'HIGH', 'MEDIUM', 'LOW'] as $severity) {
    echo str_pad($severity, 9) . ': ' . $totals[$severity] . PHP_EOL;
}
echo PHP_EOL;

if (!$findings) {
    echo "Nenhum achado pelas regras basicas." . PHP_EOL;
    exit(0);
}

echo "Achados:" . PHP_EOL;
$maxPrint = 120;
$printed = 0;
foreach ($findings as $item) {
    if ($printed >= $maxPrint) {
        break;
    }
    echo "[{$item['severity']}] {$item['rule']} {$item['file']}:{$item['line']}" . PHP_EOL;
    echo "  {$item['message']}" . PHP_EOL;
    if ($item['excerpt'] !== '') {
        echo "  > {$item['excerpt']}" . PHP_EOL;
    }
    $printed++;
}
if (count($findings) > $maxPrint) {
    echo PHP_EOL . "Total de achados excedeu limite de exibicao ({$maxPrint})." . PHP_EOL;
    echo "Use filtros adicionais ou reduza escopo para investigacao detalhada." . PHP_EOL;
}

$exit = ($totals['CRITICAL'] > 0 || $totals['HIGH'] > 0) ? 1 : 0;
exit($exit);
