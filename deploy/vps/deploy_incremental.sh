#!/usr/bin/env bash
set -euo pipefail

# Uso:
#   bash /tmp/deploy_fbcontrol_incremental.sh 20260730_150000
# O pacote correspondente deve estar em /tmp/fbcontrol_incremental_<TS>.tar.gz.

TS="${1:?Informe o identificador do deploy.}"
APP="${APP_DIR:-/var/www/apps/fbcontrol}"
PACKAGE="/tmp/fbcontrol_incremental_${TS}.tar.gz"
NEW="${APP}/releases/${TS}_fbcontrol_4_4"
BACKUP="${APP}/backups/incremental_${TS}"
CURRENT="$(readlink -f "${APP}/current")"

if [[ ! -f "${PACKAGE}" ]]; then
    echo "Pacote nao encontrado: ${PACKAGE}"
    exit 1
fi
if [[ ! -d "${CURRENT}" ]]; then
    echo "Release atual invalida: ${CURRENT}"
    exit 1
fi
if [[ -e "${NEW}" ]]; then
    echo "Release de destino ja existe: ${NEW}"
    exit 1
fi

echo "== Backup da release atual =="
mkdir -p "${BACKUP}" "${NEW}"
tar -czf "${BACKUP}/release_anterior.tar.gz" -C "${CURRENT}" .

echo "== Extraindo nova release =="
tar -xzf "${PACKAGE}" -C "${NEW}"

echo "== Preservando configuracao e dados persistentes =="
if [[ -f "${CURRENT}/config/config.local.php" ]]; then
    install -D -m 0640 "${CURRENT}/config/config.local.php" "${NEW}/config/config.local.php"
fi
if [[ -d "${CURRENT}/logs" ]]; then
    cp -a "${CURRENT}/logs/." "${NEW}/logs/"
fi
if [[ -d "${CURRENT}/public/uploads" ]]; then
    cp -a "${CURRENT}/public/uploads/." "${NEW}/public/uploads/"
fi
mkdir -p "${NEW}/logs" "${NEW}/public/uploads/profiles" "${NEW}/public/uploads/vouchers"

echo "== Validando codigo =="
find "${NEW}/app" "${NEW}/public" -type f -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
php "${NEW}/tools/check_release_candidate.php" 4.4

echo "== Ajustando permissoes =="
chown -R www-data:www-data "${NEW}"
find "${NEW}" -type d -exec chmod 0755 {} \;
find "${NEW}" -type f -exec chmod 0644 {} \;
chmod -R 0775 "${NEW}/logs" "${NEW}/public/uploads"

echo "== Ativando nova release =="
ln -s "${NEW}" "${APP}/current.next"
mv -Tf "${APP}/current.next" "${APP}/current"
systemctl reload apache2
systemctl restart php*-fpm 2>/dev/null || true

echo "== Validacao pos-deploy =="
APP_ENV=production php "${APP}/current/tools/healthcheck_fbcontrol.php" --strict
php "${APP}/current/tools/check_db_context.php"

echo "Deploy incremental concluido."
echo "Release ativa: $(readlink -f "${APP}/current")"
echo "Backup: ${BACKUP}/release_anterior.tar.gz"
