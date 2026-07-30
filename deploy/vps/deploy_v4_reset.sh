#!/usr/bin/env bash
set -euo pipefail

# Uso:
# RESET_CONFIRM=RESET_FBCONTROL_V4_4 PRUNE_OLD_RELEASES=1 \
#   bash /tmp/deploy_v4_reset.sh 20260727_120000 admin@oca-tools.com.br

TS="${1:?Informe o identificador da release.}"
ADMIN_EMAIL="${2:?Informe o e-mail do administrador que sera preservado.}"
APP="${APP_DIR:-/var/www/apps/fbcontrol}"
PACKAGE="/tmp/fbcontrol_v4_4_${TS}.tar.gz"
NEW="${APP}/releases/${TS}_fbcontrol_v4_4"
BACKUP="${APP}/backups/v4_reset_${TS}"
TMP="/tmp/fbcontrol_v4_${TS}"
CURRENT="$(readlink -f "${APP}/current")"
APACHE_STOPPED=0

if [[ "${RESET_CONFIRM:-}" != "RESET_FBCONTROL_V4_4" ]]; then
    echo "Reset bloqueado. Execute com RESET_CONFIRM=RESET_FBCONTROL_V4_4."
    exit 1
fi
if [[ ! -f "${PACKAGE}" ]]; then
    echo "Pacote nao encontrado: ${PACKAGE}"
    exit 1
fi
if [[ ! -d "${CURRENT}" ]]; then
    echo "Release atual invalida: ${CURRENT}"
    exit 1
fi

restart_apache_if_needed() {
    if [[ "${APACHE_STOPPED}" == "1" ]]; then
        systemctl start apache2 || true
    fi
}
trap restart_apache_if_needed EXIT

echo "== Preparando backup =="
mkdir -p "${BACKUP}" "${TMP}" "${NEW}"
tar -czf "${BACKUP}/release_anterior.tar.gz" -C "${CURRENT}" .

echo "== Extraindo versao 4.4 =="
tar -xzf "${PACKAGE}" -C "${NEW}"

if [[ -f "${CURRENT}/config/config.local.php" && "${CURRENT}/config/config.local.php" != "${NEW}/config/config.local.php" ]]; then
    install -D -m 0640 "${CURRENT}/config/config.local.php" "${NEW}/config/config.local.php"
fi
if [[ -d "${CURRENT}/logs" && "${CURRENT}/logs" != "${NEW}/logs" ]]; then
    cp -a "${CURRENT}/logs/." "${NEW}/logs/"
fi
if [[ -d "${CURRENT}/public/uploads" && "${CURRENT}/public/uploads" != "${NEW}/public/uploads" ]]; then
    cp -a "${CURRENT}/public/uploads/." "${NEW}/public/uploads/"
fi
mkdir -p "${NEW}/public/uploads/profiles" "${NEW}/public/uploads/vouchers" "${NEW}/logs"

# Releases legadas podem nao conter o utilitario de backup. A versao nova ja
# recebeu a configuracao ativa e e usada somente para gerar o dump preventivo.
echo "== Backup do banco de producao =="
php "${NEW}/tools/backup_database.php" "${BACKUP}/banco_antes_do_reset.sql"

echo "== Validando codigo =="
find "${NEW}/app" "${NEW}/public" -type f -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
php "${NEW}/tools/check_release_candidate.php" 4.4

chown -R www-data:www-data "${NEW}"
find "${NEW}" -type d -exec chmod 0755 {} \;
find "${NEW}" -type f -exec chmod 0644 {} \;
chmod -R 0775 "${NEW}/logs" "${NEW}/public/uploads"

echo "== Interrompendo aplicacao para reset consistente =="
systemctl stop apache2
APACHE_STOPPED=1

echo "== Aplicando estruturas idempotentes =="
php "${NEW}/tools/apply_audit_security_migration.php"
php "${NEW}/tools/apply_reservation_idempotency_migration.php"

echo "== Limpando dados operacionais =="
php "${NEW}/tools/reset_operational_data.php" \
    --admin-email="${ADMIN_EMAIL}" \
    --apply \
    --confirm=RESET_FBCONTROL_V4_4

echo "== Limpando anexos operacionais =="
find "${NEW}/public/uploads/vouchers" -mindepth 1 -not -name '.htaccess' -delete
find "${NEW}/public/uploads/profiles" -mindepth 1 -not -name '.htaccess' -delete

echo "== Ativando versao 4.4 =="
ln -sfn "${NEW}" "${APP}/current"
systemctl start apache2
APACHE_STOPPED=0
systemctl restart php*-fpm 2>/dev/null || true

echo "== Validando producao =="
APP_ENV=production php "${APP}/current/tools/healthcheck_fbcontrol.php" --strict
php "${APP}/current/tools/check_db_context.php"
php -r 'require "'"${APP}"'"/current/app/bootstrap_cli.php"; $db=Database::getInstance(); echo "Usuarios ativos: ".$db->query("SELECT COUNT(*) FROM usuarios WHERE ativo=1")->fetchColumn().PHP_EOL; echo "Reservas tematicas: ".$db->query("SELECT COUNT(*) FROM reservas_tematicas")->fetchColumn().PHP_EOL; echo "Auditoria: ".$db->query("SELECT COUNT(*) FROM auditoria")->fetchColumn().PHP_EOL;'

if [[ "${PRUNE_OLD_RELEASES:-0}" == "1" ]]; then
    echo "== Removendo releases antigas apos backup =="
    find "${APP}/releases" -mindepth 1 -maxdepth 1 -type d ! -name "$(basename "${NEW}")" -exec rm -rf {} +
fi

echo "Deploy e reset concluidos."
echo "Release ativa: $(readlink -f "${APP}/current")"
echo "Backup: ${BACKUP}"
