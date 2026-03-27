#!/usr/bin/env bash
set -euo pipefail

SRC_DIR="${SRC_DIR:-/opt/ocafbcontrol}"
DST_DIR="${DST_DIR:-/var/www/ocafbcontrol}"

echo "Sincronizando ${SRC_DIR} -> ${DST_DIR} (modo seguro)..."
rsync -av --delete \
  --exclude='.git' \
  --exclude='deploy' \
  --exclude='docs' \
  --exclude='config/config.local.php' \
  --exclude='public/uploads/' \
  "${SRC_DIR}/" "${DST_DIR}/"

echo "Reiniciando Apache..."
systemctl restart apache2

echo "Concluído."
