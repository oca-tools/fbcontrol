#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/apps/fbcontrol/current}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/fbcontrol}"
KEEP_DAYS="${KEEP_DAYS:-14}"
RESTORE_DRILL="${RESTORE_DRILL:-yes}"
MYSQL_ROOT_USER="${MYSQL_ROOT_USER:-root}"
MYSQL_ROOT_PASS="${MYSQL_ROOT_PASS:-}"

if [[ ! -f "${APP_DIR}/config/config.php" ]]; then
  echo "config.php nao encontrado em ${APP_DIR}"
  exit 1
fi

read_db_config() {
  php -r '
    $cfg = require $argv[1];
    $db = $cfg["db"] ?? [];
    echo ($db["host"] ?? "127.0.0.1") . "|" . ($db["name"] ?? "") . "|" . ($db["user"] ?? "") . "|" . ($db["pass"] ?? "");
  ' "${APP_DIR}/config/config.php"
}

IFS='|' read -r DB_HOST DB_NAME DB_USER DB_PASS <<<"$(read_db_config)"
if [[ -z "${DB_NAME}" || -z "${DB_USER}" ]]; then
  echo "Falha ao identificar credenciais de banco em ${APP_DIR}/config/config.php"
  exit 1
fi

STAMP="$(date +%F_%H%M%S)"
RUN_DIR="${BACKUP_DIR}/${STAMP}"
mkdir -p "${RUN_DIR}"

echo "[1/4] Dump do banco..."
mysqldump --single-transaction --routines --triggers \
  -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
  | gzip -9 >"${RUN_DIR}/db.sql.gz"
gzip -t "${RUN_DIR}/db.sql.gz"

echo "[2/4] Backup de arquivos criticos..."
tar -czf "${RUN_DIR}/app_bundle.tar.gz" \
  -C "${APP_DIR}" \
  config \
  public/uploads \
  sql
tar -tzf "${RUN_DIR}/app_bundle.tar.gz" >/dev/null

echo "[3/4] Checksums..."
(
  cd "${RUN_DIR}"
  sha256sum db.sql.gz app_bundle.tar.gz >SHA256SUMS
)

echo "[4/4] Restore drill..."
ROOT_AUTH=(-u "${MYSQL_ROOT_USER}")
if [[ -n "${MYSQL_ROOT_PASS}" ]]; then
  ROOT_AUTH+=("-p${MYSQL_ROOT_PASS}")
fi

TEMP_DB="restore_check_${DB_NAME}_$(date +%s)"
if [[ "${RESTORE_DRILL}" == "yes" ]]; then
  if mysql "${ROOT_AUTH[@]}" -e "SELECT 1" >/dev/null 2>&1; then
    mysql "${ROOT_AUTH[@]}" -e "CREATE DATABASE \`${TEMP_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    gunzip -c "${RUN_DIR}/db.sql.gz" | mysql "${ROOT_AUTH[@]}" "${TEMP_DB}"
    TABLE_COUNT="$(mysql "${ROOT_AUTH[@]}" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${TEMP_DB}';")"
    mysql "${ROOT_AUTH[@]}" -e "DROP DATABASE \`${TEMP_DB}\`;"
    echo "Restore drill OK: ${TABLE_COUNT} tabelas restauradas."
  else
    echo "Restore drill pulado: root MySQL indisponivel (defina MYSQL_ROOT_PASS)."
  fi
else
  echo "Restore drill desativado (RESTORE_DRILL=${RESTORE_DRILL})."
fi

find "${BACKUP_DIR}" -mindepth 1 -maxdepth 1 -type d -mtime +"${KEEP_DAYS}" -exec rm -rf {} +
echo "Backup concluido em ${RUN_DIR}"
