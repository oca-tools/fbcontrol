#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/ocafbcontrol}"
DOMAIN="${DOMAIN:-}"
DB_NAME="${DB_NAME:-controle_ab}"
DB_USER="${DB_USER:-controle_ab_user}"
DB_PASS="${DB_PASS:-troque_esta_senha}"
MYSQL_ROOT_PASS="${MYSQL_ROOT_PASS:-}"
PHP_VERSION="${PHP_VERSION:-8.3}"
ADMIN_NAME="${ADMIN_NAME:-Admin OCA}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@localhost}"
ADMIN_PASS="${ADMIN_PASS:-}"
APP_TIMEZONE="${APP_TIMEZONE:-America/Sao_Paulo}"
APP_SESSION_TIMEOUT_MIN="${APP_SESSION_TIMEOUT_MIN:-30}"
APP_ENV="${APP_ENV:-production}"
RUN_POST_HARDENING="${RUN_POST_HARDENING:-no}"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Execute como root: sudo bash deploy/vps/install.sh"
  exit 1
fi

echo "[1/8] Instalando pacotes base..."
apt update
apt install -y apache2 mysql-server php${PHP_VERSION} php${PHP_VERSION}-cli php${PHP_VERSION}-mysql php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-curl php${PHP_VERSION}-zip libapache2-mod-php${PHP_VERSION} unzip

echo "[2/8] Configurando banco de dados..."
MYSQL_CMD=(mysql -u root)
if [[ -n "$MYSQL_ROOT_PASS" ]]; then
  MYSQL_CMD+=("-p${MYSQL_ROOT_PASS}")
fi

"${MYSQL_CMD[@]}" <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "[3/8] Publicando aplicacao em ${APP_DIR}..."
mkdir -p "${APP_DIR}"
rsync -av --delete --exclude='.git' --exclude='deploy' --exclude='docs' ./ "${APP_DIR}/"

if [[ ! -f "${APP_DIR}/config/config.local.php" ]]; then
  cp "${APP_DIR}/config/config.local.example.php" "${APP_DIR}/config/config.local.php"
fi

php -r '
    $dest = $argv[1];
    $cfg = [
        "app" => [
            "base_url" => "/",
            "timezone" => $argv[2],
            "session_timeout_min" => (int)$argv[3],
            "favicon_path" => "/assets/favicon-fb-white.svg",
        ],
        "db" => [
            "host" => "127.0.0.1",
            "name" => $argv[4],
            "user" => $argv[5],
            "pass" => $argv[6],
            "charset" => "utf8mb4",
        ],
    ];
    file_put_contents($dest, "<?php\nreturn " . var_export($cfg, true) . ";\n");
' "${APP_DIR}/config/config.local.php" "${APP_TIMEZONE}" "${APP_SESSION_TIMEOUT_MIN}" "${DB_NAME}" "${DB_USER}" "${DB_PASS}"

echo "[4/8] Importando schema consolidado 2.1..."
SCHEMA_FILE="${APP_DIR}/sql/schema_v2_1_final.sql"
if [[ ! -f "$SCHEMA_FILE" ]]; then
  echo "Arquivo nao encontrado: $SCHEMA_FILE"
  exit 1
fi
mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < "$SCHEMA_FILE"

if [[ -z "${ADMIN_PASS}" ]]; then
  ADMIN_PASS="$(tr -dc 'A-Za-z0-9@#%+=' </dev/urandom | head -c 14 || true)"
  if [[ -z "${ADMIN_PASS}" ]]; then
    ADMIN_PASS="TrocarSenha123!"
  fi
fi

ADMIN_PASS_HASH="$(php -r 'echo password_hash($argv[1], PASSWORD_DEFAULT);' "${ADMIN_PASS}")"
mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" <<SQL
INSERT INTO usuarios (nome, email, senha, perfil, ativo, criado_em)
VALUES ('${ADMIN_NAME}', '${ADMIN_EMAIL}', '${ADMIN_PASS_HASH}', 'admin', 1, NOW())
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome),
  senha = VALUES(senha),
  perfil = 'admin',
  ativo = 1;
SQL

echo "[5/8] Ajustando permissoes..."
chown -R www-data:www-data "${APP_DIR}"
find "${APP_DIR}" -type d -exec chmod 755 {} \;
find "${APP_DIR}" -type f -exec chmod 644 {} \;
mkdir -p "${APP_DIR}/public/uploads/profiles" "${APP_DIR}/public/uploads/vouchers"
chmod -R 775 "${APP_DIR}/public/uploads"
chown root:www-data "${APP_DIR}/config/config.local.php"
chmod 640 "${APP_DIR}/config/config.local.php"

echo "[6/8] Configurando Apache..."
cat > /etc/apache2/conf-available/ocafbcontrol-env.conf <<APACHEENV
SetEnv APP_ENV "${APP_ENV}"
SetEnv APP_TIMEZONE "${APP_TIMEZONE}"
SetEnv APP_SESSION_TIMEOUT_MIN "${APP_SESSION_TIMEOUT_MIN}"
SetEnv DB_HOST "127.0.0.1"
SetEnv DB_NAME "${DB_NAME}"
SetEnv DB_USER "${DB_USER}"
SetEnv DB_PASS "${DB_PASS}"
APACHEENV
chown root:www-data /etc/apache2/conf-available/ocafbcontrol-env.conf
chmod 640 /etc/apache2/conf-available/ocafbcontrol-env.conf

cat > /etc/apache2/sites-available/ocafbcontrol.conf <<APACHE
<VirtualHost *:80>
    ServerName ${DOMAIN:-_}
    DocumentRoot ${APP_DIR}/public

    <Directory ${APP_DIR}/public>
        AllowOverride All
        Require all granted
        Options FollowSymLinks
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/ocafbcontrol_error.log
    CustomLog \${APACHE_LOG_DIR}/ocafbcontrol_access.log combined
</VirtualHost>
APACHE

a2enmod rewrite headers
a2enconf ocafbcontrol-env

a2dissite 000-default || true
a2ensite ocafbcontrol.conf
systemctl restart apache2

echo "[7/8] Pos-instalacao de hardening (opcional)..."
if [[ "${RUN_POST_HARDENING}" == "yes" && -f "${APP_DIR}/deploy/vps/hardening.sh" ]]; then
  bash "${APP_DIR}/deploy/vps/hardening.sh"
else
  echo "Hardening automatico ignorado (RUN_POST_HARDENING=${RUN_POST_HARDENING})."
fi

echo "[8/8] Concluido."
echo "URL: http://${DOMAIN:-SEU_IP_PUBLICO}/"
echo "Se usar dominio real, ative SSL com: certbot --apache -d seu-dominio"
echo "Admin inicial: ${ADMIN_EMAIL}"
echo "Senha admin inicial: ${ADMIN_PASS}"
