#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "${ROOT_DIR}"

echo "[1/2] PHP lint..."
php -r '
  $paths = ["app", "public", "config"];
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(".", FilesystemIterator::SKIP_DOTS));
  $count = 0;
  foreach ($it as $f) {
    if (!$f->isFile()) { continue; }
    $p = str_replace("\\\\", "/", $f->getPathname());
    $ok = false;
    foreach ($paths as $base) {
      if (str_starts_with(ltrim($p, "./"), $base . "/")) { $ok = true; break; }
    }
    if (!$ok || !preg_match("/\\.php$/i", $p)) { continue; }
    passthru("php -l " . escapeshellarg($p), $code);
    if ($code !== 0) { exit($code); }
    $count++;
  }
  echo "Lint finalizado: {$count} arquivos." . PHP_EOL;
'

echo "[2/2] SAST baseline..."
php deploy/security/sast_scan.php
